-- Adds user accounts and login sessions, for the register/login flow that
-- now gates the app before the quiz homepage.
--
-- Run against the same database as the rest of the app, e.g.:
--   psql -U <user> -d travel-match -f backend/database/migrations/001_create_users_table.sql

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    -- Strength label ('weak' | 'fair' | 'good' | 'strong') assessed at
    -- registration time and kept for reference; it is not re-derived later.
    password_strength VARCHAR(20) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Opaque bearer tokens for logged-in sessions. Only a hash of the token is
-- stored so a database read alone can never yield a usable session token.
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    expires_at TIMESTAMPTZ NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_user_sessions_token_hash ON user_sessions (token_hash);
