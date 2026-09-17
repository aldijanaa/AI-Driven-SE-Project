<?php

/**
 * Semantic search support. No pgvector extension is used - it isn't
 * available on every Postgres install (including this project's local
 * one), and at this catalog size (a few dozen destinations) a brute-force
 * cosine similarity scan in PHP is more than fast enough, so embeddings are
 * just stored as a JSONB array of floats on `destinations.embedding`.
 */

/**
 * Builds the text that gets embedded for a destination: enough to capture
 * what it's actually like, so a free-text query like "cheap chill beach
 * town with good nightlife" can match it semantically - not just by exact
 * tag/keyword overlap like the quiz's interest matching does.
 */
function buildDestinationEmbeddingText(array $dest, array $chunks): string
{
    $tags = implode(', ', array_map(fn ($t) => str_replace('_', ' ', $t), $dest['vibe_tags'] ?? []));
    $factsBlock = $chunks ? implode('. ', $chunks) : '';

    return trim(
        "{$dest['name']}, {$dest['country']}. Vibe: {$tags}. " .
        "Budget level: {$dest['budget_level']}. {$factsBlock}"
    );
}

function embedText(string $text): ?array
{
    $apiKey = getenv('GEMINI_API_KEY');
    if (!$apiKey) {
        return null;
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent';
    $payload = json_encode([
        'model' => 'models/gemini-embedding-001',
        'content' => ['parts' => [['text' => $text]]],
        // 768 keeps the stored vectors small; gemini-embedding-001 defaults
        // to 3072 dims, far more precision than this catalog size needs.
        'outputDimensionality' => 768,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "x-goog-api-key: {$apiKey}",
        ],
    ] + curlCaOptions());
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $status !== 200) {
        return null;
    }

    $values = json_decode($response, true)['embedding']['values'] ?? null;

    return is_array($values) ? $values : null;
}

function cosineSimilarity(array $a, array $b): float
{
    if (!$a || count($a) !== count($b)) {
        return 0.0;
    }

    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;
    foreach ($a as $i => $valueA) {
        $valueB = $b[$i];
        $dot += $valueA * $valueB;
        $normA += $valueA ** 2;
        $normB += $valueB ** 2;
    }

    if ($normA <= 0 || $normB <= 0) {
        return 0.0;
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}
