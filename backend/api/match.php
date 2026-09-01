<?php

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Destinations.php';
require_once __DIR__ . '/../src/Matcher.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$answers = json_decode($raw, true);

if (!is_array($answers)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$requiredFields = ['interests', 'style', 'weather', 'budgetLevel', 'companions'];
foreach ($requiredFields as $field) {
    if (!array_key_exists($field, $answers)) {
        http_response_code(422);
        echo json_encode(['error' => "Missing required field: {$field}"]);
        exit;
    }
}

$results = matchDestinations($answers, getDestinations());

logSubmission($answers, $results[0] ?? null);

echo json_encode(['results' => $results]);

/**
 * Best-effort write for analytics/history; a logging failure should never
 * break the actual quiz response the user is waiting on.
 */
function logSubmission(array $answers, ?array $topMatch): void
{
    try {
        $interests = '{' . implode(',', array_map(
            fn ($i) => '"' . str_replace('"', '\\"', $i) . '"',
            $answers['interests'] ?? []
        )) . '}';

        $stmt = getDb()->prepare('
            INSERT INTO quiz_submissions (
                duration, interests, weather, companions, style,
                budget_level, budget_amount, getaway, season,
                top_match_destination_id, top_match_score
            )
            SELECT :duration, :interests, :weather, :companions, :style,
                   :budget_level, :budget_amount, :getaway, :season,
                   d.id, :top_match_score
            FROM destinations d WHERE d.name = :top_match_name
        ');

        $stmt->execute([
            ':duration' => $answers['duration'] ?? null,
            ':interests' => $interests,
            ':weather' => $answers['weather'] ?? null,
            ':companions' => $answers['companions'] ?? null,
            ':style' => $answers['style'] ?? null,
            ':budget_level' => $answers['budgetLevel'] ?? null,
            ':budget_amount' => $answers['budgetAmount'] ?? null,
            ':getaway' => $answers['getaway'] ?? null,
            ':season' => $answers['season'] ?? null,
            ':top_match_score' => $topMatch['match'] ?? null,
            ':top_match_name' => $topMatch['name'] ?? '',
        ]);
    } catch (Throwable $e) {
        // Non-fatal: the quiz result itself is already computed and correct.
    }
}
