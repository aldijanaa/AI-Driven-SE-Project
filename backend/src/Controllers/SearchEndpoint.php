<?php

require_once __DIR__ . '/../Services/Embeddings.php';

/**
 * Free-text semantic search over the destination catalog: embeds the
 * user's query, embeds each destination (precomputed - see
 * scripts/generate-embeddings.php), and ranks by cosine similarity. This is
 * the one place in the app that actually uses embeddings - the quiz's
 * interest matching deliberately doesn't (see README), since interests are
 * a small fixed vocabulary that already maps exactly to curated tags.
 *
 * @param callable $embedQuery              (string $query): ?array
 * @param callable $getSearchableDestinations (): array  each with an 'embedding' key (array|null)
 */
function handleSearchRequest(string $method, ?string $query, callable $embedQuery, callable $getSearchableDestinations): array
{
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'GET') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $query = trim((string) $query);
    if ($query === '') {
        return ['status' => 422, 'body' => ['error' => 'Missing required query parameter: q']];
    }

    $queryEmbedding = $embedQuery($query);
    if (!$queryEmbedding) {
        return ['status' => 503, 'body' => ['error' => 'Semantic search is unavailable right now']];
    }

    $scored = [];
    foreach ($getSearchableDestinations() as $dest) {
        if (empty($dest['embedding'])) {
            continue;
        }
        $dest['similarity'] = cosineSimilarity($queryEmbedding, $dest['embedding']);
        unset($dest['embedding']);
        $scored[] = $dest;
    }

    usort($scored, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

    return ['status' => 200, 'body' => ['destinations' => array_slice($scored, 0, 8)]];
}
