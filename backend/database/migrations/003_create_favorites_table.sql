-- The 5th core domain entity: a user's saved destinations. A join between
-- users and destinations, exposed as the heart button on every suggestion
-- and the Favorites page under the profile menu.

CREATE TABLE IF NOT EXISTS favorites (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    destination_id INTEGER NOT NULL REFERENCES destinations(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (user_id, destination_id)
);

CREATE INDEX IF NOT EXISTS idx_favorites_user_id ON favorites (user_id);
