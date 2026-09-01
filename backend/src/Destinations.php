<?php

require_once __DIR__ . '/../database/Database.php';

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
            array_to_json(vibe_tags) AS vibe_tags
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
