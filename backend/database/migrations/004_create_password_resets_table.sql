-- Forgot-password tokens. Only a hash of the token is stored (same approach
-- as user_sessions), so a database read alone never yields a usable link.
-- A row is deleted once consumed or replaced by a newer request for the
-- same user, so "not found" already covers used/expired/superseded.

CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    expires_at TIMESTAMPTZ NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_password_resets_token_hash ON password_resets (token_hash);
CREATE INDEX IF NOT EXISTS idx_password_resets_user_id ON password_resets (user_id);
