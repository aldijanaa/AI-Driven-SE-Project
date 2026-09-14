<?php

require_once __DIR__ . '/../database/Database.php';

const MATCH_REQUIRED_FIELDS = ['interests', 'style', 'weather', 'budgetLevel', 'companions'];

/**
 * Handles a request to POST /api/match: validates it, runs the match, and
 * best-effort logs the submission. $getDestinations/$matchDestinations/
 * $logSubmission are injected so this can be unit tested without a live
 * database or RAG call - callers in production pass the real functions.
 * $userId is the logged-in user making the request, or null for a guest -
 * it's recorded alongside the submission so it can show up in that user's
 * history, but a quiz can still be run without an account.
 *
 * @return array{status: int, body: ?array}
 */
function handleMatchRequest(
    string $method,
    string $rawBody,
    ?int $userId,
    callable $getDestinations,
    callable $matchDestinations,
    callable $logSubmission
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $answers = json_decode($rawBody, true);

    if (!is_array($answers)) {
        return ['status' => 400, 'body' => ['error' => 'Invalid JSON body']];
    }

    foreach (MATCH_REQUIRED_FIELDS as $field) {
        if (!array_key_exists($field, $answers)) {
            return ['status' => 422, 'body' => ['error' => "Missing required field: {$field}"]];
        }
    }

    $results = $matchDestinations($answers, $getDestinations());

    $logSubmission($answers, $results, $userId);

    return ['status' => 200, 'body' => ['results' => $results]];
}

/**
 * Best-effort write for analytics/history; a logging failure should never
 * break the actual quiz response the user is waiting on. Stores the full
 * results (not just the top match) so a logged-in user's history page can
 * show exactly what they were shown.
 */
function logSubmission(array $answers, array $results, ?int $userId): void
{
    try {
        $topMatch = $results[0] ?? null;

        $interests = '{' . implode(',', array_map(
            fn ($i) => '"' . str_replace('"', '\\"', $i) . '"',
            $answers['interests'] ?? []
        )) . '}';

        $stmt = getDb()->prepare('
            INSERT INTO quiz_submissions (
                user_id, duration, interests, weather, companions, style,
                budget_level, budget_amount, getaway, season, results,
                top_match_destination_id, top_match_score
            )
            SELECT :user_id, :duration, :interests, :weather, :companions, :style,
                   :budget_level, :budget_amount, :getaway, :season, :results,
                   d.id, :top_match_score
            FROM destinations d WHERE d.name = :top_match_name
        ');

        $stmt->execute([
            ':user_id' => $userId,
            ':duration' => $answers['duration'] ?? null,
            ':interests' => $interests,
            ':weather' => $answers['weather'] ?? null,
            ':companions' => $answers['companions'] ?? null,
            ':style' => $answers['style'] ?? null,
            ':budget_level' => $answers['budgetLevel'] ?? null,
            ':budget_amount' => $answers['budgetAmount'] ?? null,
            ':getaway' => $answers['getaway'] ?? null,
            ':season' => $answers['season'] ?? null,
            ':results' => json_encode($results),
            ':top_match_score' => $topMatch['match'] ?? null,
            ':top_match_name' => $topMatch['name'] ?? '',
        ]);
    } catch (Throwable $e) {
        // Non-fatal: the quiz result itself is already computed and correct.
    }
}
