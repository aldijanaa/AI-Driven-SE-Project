-- Adds storage for a semantic-search embedding per destination, used by
-- the free-text search on the Explore page (src/SearchEndpoint.php).
-- Stored as JSONB (an array of floats) rather than requiring the pgvector
-- extension, since it isn't available on every Postgres install - at this
-- catalog size, a brute-force cosine similarity scan in PHP is plenty fast
-- and needs no index. Populated by backend/scripts/generate-embeddings.php,
-- not by this migration.

ALTER TABLE destinations
    ADD COLUMN IF NOT EXISTS embedding JSONB;
