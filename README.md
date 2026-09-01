# TravelMatch

A quiz-based travel recommender: answer a few questions about your travel
preferences and get matched destinations with a suggested time to go, a
budget estimate, and a recommended stay length.

## Structure

- `frontend/` — React (Vite) quiz UI.
- `backend/` — PHP API that scores destinations against your answers and
  generates personalized descriptions via RAG (destination knowledge base +
  Google Gemini), reading from and writing to PostgreSQL.
- `database/` — `schema.sql` (tables + seed data) for the PostgreSQL database.
- `mcp-server/` — MCP server exposing the matching engine as a tool
  (`get_travel_recommendations`) for Claude Desktop/Code or other MCP
  clients.
- `automation/` — N8N workflow that emails a user their results.

## Running locally

```
# database — create a database and run database/schema.sql against it
# (via pgAdmin's Query Tool, or Docker — see database/README.md for both)

# backend (from repo root) — set DB_* vars in backend/.env first (see .env.example)
php -S localhost:8000 -t backend

# frontend (in another terminal)
cd frontend
npm install
npm run dev
```

The frontend expects the API at `http://localhost:8000/api` — override with
`VITE_API_URL` in `frontend/.env` if needed (see `.env.example`).

### Enabling AI-generated descriptions

Get a free key at https://aistudio.google.com/apikey, copy
`backend/.env.example` to `backend/.env`, and set `GEMINI_API_KEY`.
Without it, the backend falls back to templated descriptions — the app
stays fully functional either way.

### Enabling "email me my results"

Set up the N8N workflow in `automation/` (see `automation/README.md`), then
put its webhook URL in `backend/.env` as `N8N_WEBHOOK_URL`.

### MCP server

See `mcp-server/README.md`.
