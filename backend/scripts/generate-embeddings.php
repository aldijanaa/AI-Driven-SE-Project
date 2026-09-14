<?php

/**
 * One-off / re-runnable maintenance script: generates a semantic-search
 * embedding for any destination that doesn't have one yet (run again after
 * adding new destinations, or after editing their knowledge chunks).
 *
 * Requires GEMINI_API_KEY in backend/.env.
 * Usage: php backend/scripts/generate-embeddings.php
 */

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Embeddings.php';

loadEnv(__DIR__ . '/../.env');

$stmt = getDb()->query('
    SELECT id, name, country, budget_level, array_to_json(vibe_tags) AS vibe_tags, wikipedia_extract
    FROM destinations
    WHERE embedding IS NULL
    ORDER BY id
');
$pending = $stmt->fetchAll();

if (!$pending) {
    echo "Nothing to embed - every destination already has an embedding.\n";
    exit(0);
}

$knowledgeStmt = getDb()->prepare('SELECT body FROM destination_knowledge WHERE destination_id = :id');
$update = getDb()->prepare('UPDATE destinations SET embedding = :embedding WHERE id = :id');

foreach ($pending as $dest) {
    $dest['vibe_tags'] = json_decode($dest['vibe_tags'], true) ?? [];

    $knowledgeStmt->execute([':id' => $dest['id']]);
    $chunks = array_column($knowledgeStmt->fetchAll(), 'body');
    if (!empty($dest['wikipedia_extract'])) {
        $chunks[] = $dest['wikipedia_extract'];
    }

    $text = buildDestinationEmbeddingText($dest, $chunks);
    $embedding = embedText($text);

    if (!$embedding) {
        echo "[FAILED] {$dest['name']}, {$dest['country']} - check GEMINI_API_KEY / network\n";
        continue;
    }

    $update->execute([':embedding' => json_encode($embedding), ':id' => $dest['id']]);
    echo '[OK] ' . $dest['name'] . ', ' . $dest['country'] . ' (' . count($embedding) . " dims)\n";
}
