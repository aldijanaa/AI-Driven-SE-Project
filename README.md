# TravelMatch

A quiz-based travel recommender: answer a few questions about your travel
preferences and get matched destinations with a suggested time to go, a
budget estimate, and a recommended stay length.

## Structure

- `frontend/` — React (Vite) quiz UI.
- `backend/` — PHP API that scores and ranks destinations, and generates
  personalized descriptions via RAG (destination knowledge base + Google
  Gemini), reading from and writing to PostgreSQL.
- `mcp-server/` — MCP server exposing the matching engine as a tool for
  Claude Desktop/Code or other MCP clients.
- `automation/` — n8n workflow that emails a user their results.
- `tests/` — PHPUnit tests for the backend.

## Setup

### 1. Database

The PostgreSQL database holding destinations, their knowledge base, and
quiz submissions.

- Create a database (e.g. `travel-match`).
- Run `schema.sql` against it (psql, pgAdmin, or any SQL client) to create
  the tables and seed the destination data.
- Run the files in `backend/database/migrations/` against it too, in order,
  to add the `users`/`user_sessions` tables (login/register), the
  `user_id`/`results` columns on `quiz_submissions` (quiz history), the
  `favorites` table (saved destinations), and the `password_resets` table
  (forgot password).

### 2. Backend

The PHP API that scores destinations and serves the frontend.

- Create `backend/.env` with `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
  `DB_PASSWORD` matching the database from step 1.
- Optionally add `GEMINI_API_KEY` (free key at
  https://aistudio.google.com/apikey) to enable AI-generated descriptions —
  without it, the app falls back to templated descriptions and still works.
- Optionally add `N8N_WEBHOOK_URL` to enable "email me my results",
  `N8N_PASSWORD_RESET_WEBHOOK_URL` to enable "forgot your password" emails,
  and/or `N8N_VERIFICATION_WEBHOOK_URL` to enable "verify your email" —
  set those workflows up first following `automation/README.md`. Without
  them, the relevant feature still "works" (same response either way,
  see below) but no email actually goes out.
- Set `FRONTEND_URL` (default `http://localhost:5173`) so password reset
  emails link back to the right place.
- Start it:
  ```
  php -S localhost:8000 -t backend backend/index.php
  ```

### 3. Frontend

The React quiz UI the user interacts with.

- Install and run it:
  ```
  cd frontend
  npm install
  npm run dev
  ```
- By default it calls the API at `http://localhost:8000/api`. If your
  backend runs elsewhere, set `VITE_API_URL` in `frontend/.env`.

## Deployment

Everything goes to Render — backend (Docker web service), database (managed
Postgres), and frontend (static site) — via the `render.yaml` Blueprint at
the repo root. CORS is wide open (`Access-Control-Allow-Origin: *`) and
auth uses a `Bearer` token (no cookies), so this would also work split
across origins, but one platform is simpler. Render's free tier needs no
credit card, unlike Heroku.

### 1. Deploy the Blueprint

Push this repo to GitHub, then in the Render dashboard: **New → Blueprint**,
point it at the repo. `render.yaml` provisions three things in one go:
- `travelmatch-db` — free Postgres.
- `travelmatch-backend` — Docker web service (`php:8.2-apache`, no native
  PHP runtime on Render, document root `backend/` so `/api/*.php` paths
  resolve the same way they do against the local `php -S` dev server; the
  backend itself has no runtime dependencies, the root `Dockerfile` exists
  only for this).
- `travelmatch-frontend` — static site (`npm run build` in `frontend/`,
  publishes `frontend/dist`). Static sites on Render's free tier don't
  spin down, unlike the free web service.

It'll prompt you for the vars marked `sync: false` (so they're not
committed): `GEMINI_API_KEY`, and optionally `N8N_WEBHOOK_URL`,
`N8N_PASSWORD_RESET_WEBHOOK_URL`, `N8N_VERIFICATION_WEBHOOK_URL` (same as
in local setup above — leave any of the three blank if you don't need that
email feature in prod).

`render.yaml` assumes both services keep their default names, giving
predictable URLs (`https://travelmatch-backend.onrender.com`,
`https://travelmatch-frontend.onrender.com`) that `FRONTEND_URL` and
`VITE_API_URL` are pre-wired to. If either name is already taken on Render,
it'll get a different URL instead — update `FRONTEND_URL` on the backend
service and `VITE_API_URL` on the frontend service (Settings → Environment)
to match, which triggers a redeploy of each.

### 2. Load the database

Load the schema and migrations, in order, using the backend database's
**External Database URL** from the Render dashboard (your machine isn't on
Render's internal network, so the **Internal** URL won't resolve here):

```
psql "$EXTERNAL_DATABASE_URL" < backend/database/schema.sql
for f in backend/database/migrations/*.sql; do psql "$EXTERNAL_DATABASE_URL" < "$f"; done
```

`DATABASE_URL` (wired automatically from `travelmatch-db` via
`fromDatabase` in `render.yaml`) is parsed (with `sslmode=require`) by
`backend/database/Database.php` — no `DB_HOST`/`DB_USER`/etc. env vars
needed on Render.

**Free-tier caveats:** the free backend web service spins down after
inactivity (cold start on the next request; the static frontend doesn't),
and the free Postgres database expires 30 days after creation (14-day
grace period to upgrade before it's deleted) — fine for a demo/student
project, not for anything long-lived without upgrading the plan.

## AI-generated descriptions: sources & trustworthiness

Each quiz result's description is written by an LLM (Google Gemini), but it
is not free-generated — it's produced by a retrieval-augmented generation
(RAG) pipeline (`backend/src/Rag.php`) designed so the model can only draw
on facts the app itself supplied, and so the app never silently shows
unverifiable output.

**1. Retrieval — two sourced inputs, not the model's own "knowledge":**
- A curated `destination_knowledge` table (see the migrations), ranked by
  tag overlap with the user's stated interests — up to 4 chunks per
  destination.
- A live extract from that destination's Wikipedia article, fetched at
  request time via Wikipedia's public REST API (`backend/src/Wikipedia.php`)
  and cached on the `destinations` row (`wikipedia_extract`,
  `wikipedia_url`). This is the same source linked to the user under
  "Learn more" on each destination card, so the grounding is independently
  checkable, not just asserted.

**2. Generation — constrained, not open-ended:** both sets of facts are
injected into the prompt (`buildRagPrompt`), which explicitly instructs the
model to *"ground it in the facts given below rather than generic praise"*
and caps the output at two sentences. The model is never asked to "tell me
about Lisbon" — only to explain, from the given facts, why this specific
destination fits this specific traveler.

**3. Validation and fallback — no unverified output ever reaches the
user:** `extractGeminiText()` rejects any response that isn't a single
clean paragraph (e.g. leaked reasoning from "thinking" models). If that
check fails, if Gemini errors or times out, or if no API key is configured
at all, the app falls back to `buildDescription()` — a fully deterministic,
non-LLM template built directly from the destination's stats. The user is
never shown a malformed or ungrounded response with no safety net.

**4. Transparency — the app tells you which one you're looking at:** every
result carries a `description_source` field (`"gemini"` or `"fallback"`).
The frontend only shows the "✨ AI-generated, grounded in curated facts +
Wikipedia" citation when it's actually true — a rule-based fallback
description is never mislabeled as AI-grounded.

**5. Post-generation truthfulness check — verifying the model actually used
what it was given:** the prompt *instructs* Gemini to stay grounded, but an
instruction alone doesn't guarantee compliance, so `isGrounded()` checks the
output afterward. It strips filler/stop words from both the generated
sentence and the combined source text (retrieved chunks + Wikipedia extract
+ the destination's own name/country), then requires at least 25% of the
generated sentence's remaining content words to actually appear in that
source text. A description that shares almost no vocabulary with what was
retrieved — i.e. the model answered from its own general knowledge instead
of the supplied facts — fails this check and is treated exactly like a
Gemini failure: discarded in favor of the deterministic fallback.

**Known limitations (worth being upfront about):** Wikipedia is
community-edited, not a peer-reviewed source — it's a reasonable, freely
accessible source for a student project, not an authoritative one. The
curated `destination_knowledge` facts were hand-written from general
knowledge rather than sourced from a specific citation at write time.
`isGrounded()` is a lexical overlap heuristic, not semantic verification —
it tolerates paraphrasing but can't catch a claim that's phrased using the
same words yet asserts something the source doesn't actually say; a
stronger (and costlier) version would use a second LLM call as a judge.

## Embeddings & semantic search

The interest-matching in the quiz (`Matcher.php`) deliberately does **not**
use embeddings — interests are a small, fixed vocabulary (beach, nature,
history, …) that already maps exactly to curated `destination_knowledge`
tags, so an exact structured match is both simpler and more precise than a
vector similarity search would be there.

Embeddings are used for the one thing they're actually suited for: **free-text
semantic search on the Explore page** (`backend/src/Embeddings.php`,
`SearchEndpoint.php`). Each destination is embedded (via Gemini's
`gemini-embedding-001` model, 768 dimensions, from its name, vibe tags,
curated facts, and Wikipedia extract combined — `buildDestinationEmbeddingText()`)
and stored on `destinations.embedding`, generated by
`backend/scripts/generate-embeddings.php` (re-run it after adding
destinations or editing their knowledge chunks). A search query is embedded
the same way at request time and ranked against the catalog by cosine
similarity, so a query like *"cheap chill beach town with good nightlife"*
matches on meaning, not just keyword overlap.

No vector database or Postgres extension (e.g. pgvector) is used — at this
catalog size (a few dozen destinations), a brute-force cosine similarity
scan in PHP is both simpler to set up and fast enough, so embeddings are
stored as a plain JSONB float array.

## API

- `POST /api/register` — `{ firstName, lastName, email, password }`, creates
  an account and returns `{ user, token }`.
- `POST /api/login` — `{ email, password }`, returns `{ user, token }`.
- `GET /api/me` — `Authorization: Bearer <token>`, returns the current user.
- `POST /api/logout` — `Authorization: Bearer <token>`, ends the session.
- `POST /api/match` — quiz answers in, top destination matches out. Pass
  `Authorization: Bearer <token>` to attach the submission to that user's
  history; otherwise it runs anonymously.
- `GET /api/history` — `Authorization: Bearer <token>`, returns that user's
  past quiz submissions (answers + full results), most recent first.
- `GET /api/destinations` — the full destination catalog for the Explore
  page, each with `match_count` (how many quiz submissions it was the #1
  match for), sorted most-matched first.
- `GET /api/search?q=...` — free-text semantic search over the destination
  catalog (see "Embeddings & semantic search" above). Returns up to 8
  destinations ranked by cosine similarity, each with a `similarity` score.
  Returns 503 if `GEMINI_API_KEY` isn't configured.
- `GET /api/favorites` — `Authorization: Bearer <token>`, returns that
  user's saved destinations.
- `POST /api/favorites` — `Authorization: Bearer <token>`, body
  `{ destinationId }`, toggles that destination on/off the user's favorites
  and returns `{ favorited: boolean }`.
- `POST /api/profile` — `Authorization: Bearer <token>`, body
  `{ firstName, lastName, email }`, updates the user's info and returns
  `{ user }`.
- `POST /api/change-password` — `Authorization: Bearer <token>`, body
  `{ currentPassword, newPassword }`, verifies the current password and
  sets a new one.
- `POST /api/forgot-password` — `{ email }`, always returns the same
  generic message (so the endpoint can't be used to discover which emails
  are registered); best-effort emails a one-time reset link if the account
  exists.
- `POST /api/reset-password` — `{ token, newPassword }`, consumes a reset
  link (single-use, 1-hour expiry) and sets a new password, signing the
  user out of every other session.
- `POST /api/notify` — `{ email, results }`, emails the results.
- `GET /api/health` — health check.

## Tests

```
cd backend
php vendor/bin/phpunit
```

See `mcp-server/README.md` and `automation/README.md` for those pieces.
