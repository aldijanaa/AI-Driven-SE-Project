-- Adds storage for each destination's Wikipedia-sourced photo, short
-- extract, and article URL. Populated by backend/scripts/backfill-wikipedia.php
-- (not by this migration) so the app doesn't depend on an external API call
-- at request time - and so the same extract can be reused as a trusted-
-- source grounding chunk for RAG description generation (see src/Rag.php).

ALTER TABLE destinations
    ADD COLUMN IF NOT EXISTS photo_url TEXT,
    ADD COLUMN IF NOT EXISTS wikipedia_url TEXT,
    ADD COLUMN IF NOT EXISTS wikipedia_extract TEXT;
