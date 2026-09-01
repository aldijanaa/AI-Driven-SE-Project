# TravelMatch backend

Plain PHP, no framework. Reads from PostgreSQL via `database/Database.php`
(PDO) — see `../database/README.md` to set that up; `DB_*` vars go in
`.env`.

## AI-generated descriptions (RAG)

`destination_knowledge` (in Postgres) holds a small per-destination
knowledge base; `src/Rag.php` retrieves the chunks relevant to a user's
interests and asks Google Gemini (`gemini-flash-lite-latest`) to turn them into a
personalized description for the top 3 matches, run concurrently via
`curl_multi` so the extra latency is one request's worth, not three.
Get a free API key at https://aistudio.google.com/apikey (no credit
card required), copy `.env.example` to `.env`, and set `GEMINI_API_KEY`.
Without it, `match.php` falls back to the templated description
automatically — as it also does per-destination if a Gemini response
looks malformed (too long, contains line breaks) or the request fails
outright.

Note: some Gemini "thinking" models (e.g. `gemini-3.6-flash`) spend part
of their output budget on hidden reasoning before answering and have a
much tighter free-tier quota; `gemini-flash-lite-latest` doesn't do this
and is the better fit here. If PHP's cURL fails with an SSL error on
Windows (no local CA bundle configured), that's why `cacert.pem` is
bundled here and referenced explicitly via `CURLOPT_CAINFO`.

## Run

```
php -S localhost:8000 -t backend backend/index.php
```

`index.php` is a front controller that maps clean paths (`/api/match`) to
the matching file in `api/` (`api/match.php`), so the `.php` extension
never shows up in a URL. It's only needed with PHP's built-in server; a
real Apache/Nginx deployment would use a rewrite rule to `index.php`
instead.

## Endpoints

- `POST /api/match` — body: quiz answers JSON, returns top destination matches.
- `POST /api/notify` — body: `{ email, results }`, emails the results via the n8n webhook.
- `GET /api/health` — health check.

### `/api/match` request body

```json
{
  "interests": ["beach", "food", "history"],
  "weather": "warm",
  "companions": "partner",
  "style": "balanced",
  "budgetLevel": "moderate",
  "budgetAmount": 900,
  "getaway": "romantic_luxury",
  "duration": "medium",
  "season": "flexible"
}
```
