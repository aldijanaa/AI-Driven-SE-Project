<?php

require_once __DIR__ . '/Auth.php';

/**
 * Handles GET /api/history: returns the logged-in user's past quiz
 * submissions, most recent first. $findUserBySessionTokenHash/
 * $findSubmissionsByUserId are injected so this can be unit tested without
 * a live database - callers in production pass the real Users.php
 * functions.
 *
 * @return array{status: int, body: ?array}
 */
function handleHistoryRequest(
    string $method,
    ?string $authHeader,
    callable $findUserBySessionTokenHash,
    callable $findSubmissionsByUserId
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'GET') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $token = bearerTokenFromHeader($authHeader);
    $user = $token ? $findUserBySessionTokenHash(hashSessionToken($token)) : null;

    if ($user === null) {
        return ['status' => 401, 'body' => ['error' => 'Invalid or expired session']];
    }

    return ['status' => 200, 'body' => ['submissions' => $findSubmissionsByUserId($user['id'])]];
}
