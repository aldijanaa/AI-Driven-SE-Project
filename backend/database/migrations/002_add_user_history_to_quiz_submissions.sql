-- Links quiz submissions to the account that made them, and keeps the full
-- match results (not just the top pick) so a user's history page can show
-- exactly what they saw at the time.

ALTER TABLE quiz_submissions
    ADD COLUMN IF NOT EXISTS user_id INTEGER REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE quiz_submissions
    ADD COLUMN IF NOT EXISTS results JSONB NOT NULL DEFAULT '[]'::jsonb;

CREATE INDEX IF NOT EXISTS idx_quiz_submissions_user_id ON quiz_submissions (user_id);
