<?php

/**
 * One-off / re-runnable maintenance script: fills in photo_url,
 * wikipedia_url and wikipedia_extract for any destination that doesn't
 * have them yet (run again after adding new destinations to the catalog).
 *
 * Usage: php backend/scripts/backfill-wikipedia.php
 */

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Services/Wikipedia.php';

$stmt = getDb()->query('SELECT id, name, country FROM destinations WHERE wikipedia_url IS NULL ORDER BY id');
$pending = $stmt->fetchAll();

if (!$pending) {
    echo "Nothing to backfill - every destination already has Wikipedia data.\n";
    exit(0);
}

$update = getDb()->prepare('
    UPDATE destinations
    SET photo_url = :photo_url, wikipedia_url = :wikipedia_url, wikipedia_extract = :wikipedia_extract
    WHERE id = :id
');

foreach ($pending as $dest) {
    $info = resolveDestinationWikipedia($dest['name'], $dest['country']);

    $update->execute([
        ':photo_url' => $info['photo_url'],
        ':wikipedia_url' => $info['wikipedia_url'],
        ':wikipedia_extract' => $info['wikipedia_extract'],
        ':id' => $dest['id'],
    ]);

    $status = $info['wikipedia_url'] ? 'OK' : 'NO MATCH';
    echo "[{$status}] {$dest['name']}, {$dest['country']}\n";
}
