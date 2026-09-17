<?php

require_once __DIR__ . '/../../database/Database.php';

function isFavorited(int $userId, int $destinationId): bool
{
    $stmt = getDb()->prepare('
        SELECT 1 FROM favorites WHERE user_id = :user_id AND destination_id = :destination_id
    ');
    $stmt->execute([':user_id' => $userId, ':destination_id' => $destinationId]);

    return $stmt->fetch() !== false;
}

function addFavorite(int $userId, int $destinationId): void
{
    $stmt = getDb()->prepare('
        INSERT INTO favorites (user_id, destination_id)
        VALUES (:user_id, :destination_id)
        ON CONFLICT (user_id, destination_id) DO NOTHING
    ');
    $stmt->execute([':user_id' => $userId, ':destination_id' => $destinationId]);
}

function removeFavorite(int $userId, int $destinationId): void
{
    $stmt = getDb()->prepare('
        DELETE FROM favorites WHERE user_id = :user_id AND destination_id = :destination_id
    ');
    $stmt->execute([':user_id' => $userId, ':destination_id' => $destinationId]);
}

/**
 * Loads a user's saved destinations for the Favorites page, most recently
 * saved first.
 */
function findFavoriteDestinationsByUserId(int $userId): array
{
    $stmt = getDb()->prepare('
        SELECT
            d.id, d.name, d.country, d.budget_level, d.budget_min, d.budget_max,
            d.best_month_start, d.best_month_end, d.temperature, d.stay_min, d.stay_max,
            array_to_json(d.vibe_tags) AS vibe_tags
        FROM favorites f
        JOIN destinations d ON d.id = f.destination_id
        WHERE f.user_id = :user_id
        ORDER BY f.created_at DESC
    ');
    $stmt->execute([':user_id' => $userId]);

    $intFields = ['id', 'budget_min', 'budget_max', 'best_month_start', 'best_month_end', 'temperature', 'stay_min', 'stay_max'];

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
