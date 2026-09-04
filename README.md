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

### 2. Backend

The PHP API that scores destinations and serves the frontend.

- Create `backend/.env` with `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
  `DB_PASSWORD` matching the database from step 1.
- Optionally add `GEMINI_API_KEY` (free key at
  https://aistudio.google.com/apikey) to enable AI-generated descriptions —
  without it, the app falls back to templated descriptions and still works.
- Optionally add `N8N_WEBHOOK_URL` to enable "email me my results" — set
  that workflow up first following `automation/README.md`.
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

## API

- `POST /api/match` — quiz answers in, top destination matches out.
- `POST /api/notify` — `{ email, results }`, emails the results.
- `GET /api/health` — health check.

## Tests

```
cd backend
php vendor/bin/phpunit
```

See `mcp-server/README.md` and `automation/README.md` for those pieces.
