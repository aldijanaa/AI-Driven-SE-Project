-- Email verification for signup. Existing accounts are grandfathered in as
-- already verified (DEFAULT true); new registrations explicitly insert
-- false and only get a session once they confirm their code.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified BOOLEAN NOT NULL DEFAULT true;

-- The 7th entity: one active verification code per user. Only its hash is
-- stored, same approach as user_sessions/password_resets. attempts guards
-- against brute-forcing a 6-digit code.
CREATE TABLE IF NOT EXISTS email_verifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    code_hash CHAR(64) NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    expires_at TIMESTAMPTZ NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_email_verifications_user_id ON email_verifications (user_id);
