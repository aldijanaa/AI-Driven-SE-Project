<?php

require_once __DIR__ . '/../../database/Database.php';

/**
 * Loads the destination catalog from PostgreSQL. Score fields are on a
 * 0-10 scale; temperature is the average °C during the destination's best
 * travel window. See database/schema.sql for the table definition and
 * seed data.
 */
function getDestinations(): array
{
    $stmt = getDb()->query('
        SELECT
            id, name, country, budget_level, budget_min, budget_max,
            best_month_start, best_month_end, temperature, stay_min, stay_max,
            beach_score, history_score, food_score, nature_score, nightlife_score,
            adventure_score, relaxation_score, family_score,
            array_to_json(vibe_tags) AS vibe_tags,
            photo_url, wikipedia_url, wikipedia_extract
        FROM destinations
        ORDER BY id
    ');

    $intFields = [
        'id', 'budget_min', 'budget_max', 'best_month_start', 'best_month_end',
        'temperature', 'stay_min', 'stay_max', 'beach_score', 'history_score',
        'food_score', 'nature_score', 'nightlife_score', 'adventure_score',
        'relaxation_score', 'family_score',
    ];

    $destinations = [];
    foreach ($stmt->fetchAll() as $row) {
        foreach ($intFields as $field) {
            $row[$field] = (int) $row[$field];
        }
        $row['vibe_tags'] = json_decode($row['vibe_tags'], true) ?? [];
        $destinations[] = $row;
    }

    return $destinations;
}

/**
 * Loads the destination catalog for the Explore page, each with a
 * match_count: how many quiz submissions it was the #1 match for. That's
 * the "what other people liked" signal - real usage, not a self-reported
 * like button - so it's naturally most useful once the app has some
 * history. Sorted most-matched first.
 */
function getDestinationsWithPopularity(): array
{
    $stmt = getDb()->query('
        SELECT
            id, name, country, budget_level, budget_min, budget_max,
            best_month_start, best_month_end, temperature, stay_min, stay_max,
            array_to_json(vibe_tags) AS vibe_tags,
            photo_url, wikipedia_url, wikipedia_extract,
            (
                SELECT COUNT(*) FROM quiz_submissions qs
                WHERE qs.top_match_destination_id = destinations.id
            ) AS match_count
        FROM destinations
        ORDER BY match_count DESC, name
    ');

    $intFields = [
        'id', 'budget_min', 'budget_max', 'best_month_start', 'best_month_end',
        'temperature', 'stay_min', 'stay_max', 'match_count',
    ];

    $destinations = [];
    foreach ($stmt->fetchAll() as $row) {
        foreach ($intFields as $field) {
            $row[$field] = (int) $row[$field];
        }
        $row['vibe_tags'] = json_decode($row['vibe_tags'], true) ?? [];
        $destinations[] = $row;
    }

    return $destinations;
}

/**
 * Loads the destination catalog with each one's semantic-search embedding
 * decoded to a plain float array (or null if it hasn't been generated yet -
 * see scripts/generate-embeddings.php). Used by SearchEndpoint.php.
 */
function getDestinationsWithEmbeddings(): array
{
    $stmt = getDb()->query('
        SELECT
            id, name, country, budget_level, budget_min, budget_max,
            best_month_start, best_month_end, temperature, stay_min, stay_max,
            array_to_json(vibe_tags) AS vibe_tags,
            photo_url, wikipedia_url, wikipedia_extract, embedding
        FROM destinations
    ');

    $intFields = [
        'id', 'budget_min', 'budget_max', 'best_month_start', 'best_month_end',
        'temperature', 'stay_min', 'stay_max',
    ];

    $destinations = [];
    foreach ($stmt->fetchAll() as $row) {
        foreach ($intFields as $field) {
            $row[$field] = (int) $row[$field];
        }
        $row['vibe_tags'] = json_decode($row['vibe_tags'], true) ?? [];
        $row['embedding'] = $row['embedding'] !== null ? json_decode($row['embedding'], true) : null;
        $destinations[] = $row;
    }

    return $destinations;
}
