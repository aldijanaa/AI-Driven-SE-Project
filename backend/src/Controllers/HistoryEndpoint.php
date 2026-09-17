<?php

require_once __DIR__ . '/../Core/Auth.php';

/**
 * Handles /api/history: GET returns the logged-in user's past quiz
 * submissions, most recent first; DELETE removes one submission (by
 * ?id=) from that user's history. $findUserBySessionTokenHash/
 * $findSubmissionsByUserId/$deleteSubmissionByUserId are injected so this
 * can be unit tested without a live database - callers in production pass
 * the real Users.php functions.
 *
 * @return array{status: int, body: ?array}
 */
function handleHistoryRequest(
    string $method,
    ?string $authHeader,
    callable $findUserBySessionTokenHash,
    callable $findSubmissionsByUserId,
    ?string $submissionIdParam = null,
    ?callable $deleteSubmissionByUserId = null
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'GET' && $method !== 'DELETE') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $token = bearerTokenFromHeader($authHeader);
    $user = $token ? $findUserBySessionTokenHash(hashSessionToken($token)) : null;

    if ($user === null) {
        return ['status' => 401, 'body' => ['error' => 'Invalid or expired session']];
    }

    if ($method === 'GET') {
        return ['status' => 200, 'body' => ['submissions' => $findSubmissionsByUserId($user['id'])]];
    }

    $submissionId = filter_var($submissionIdParam, FILTER_VALIDATE_INT);

    if ($submissionId === false) {
        return ['status' => 422, 'body' => ['error' => 'Missing required field: id']];
    }

    if (!$deleteSubmissionByUserId($user['id'], $submissionId)) {
        return ['status' => 404, 'body' => ['error' => 'Submission not found']];
    }

    return ['status' => 200, 'body' => ['deleted' => true]];
}
