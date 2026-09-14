<?php

/**
 * Handles GET /api/destinations: the full catalog for the Explore page,
 * each with how many quiz-takers were matched to it. $getDestinations is
 * injected so this can be unit tested without a live database - callers in
 * production pass getDestinationsWithPopularity().
 *
 * @return array{status: int, body: ?array}
 */
function handleExploreRequest(string $method, callable $getDestinations): array
{
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'GET') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    return ['status' => 200, 'body' => ['destinations' => $getDestinations()]];
}
