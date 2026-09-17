<?php

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Services/PasswordPolicy.php';

/**
 * Handles POST /api/profile: updates the logged-in user's name and email.
 * $findUserBySessionTokenHash/$findUserByEmail/$updateUserProfile are
 * injected so this can be unit tested without a live database - callers in
 * production pass the real Users.php functions.
 *
 * @return array{status: int, body: ?array}
 */
function handleUpdateProfileRequest(
    string $method,
    string $rawBody,
    ?string $authHeader,
    callable $findUserBySessionTokenHash,
    callable $findUserByEmail,
    callable $updateUserProfile
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $token = bearerTokenFromHeader($authHeader);
    $user = $token ? $findUserBySessionTokenHash(hashSessionToken($token)) : null;

    if ($user === null) {
        return ['status' => 401, 'body' => ['error' => 'Invalid or expired session']];
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        return ['status' => 400, 'body' => ['error' => 'Invalid JSON body']];
    }

    foreach (['firstName', 'lastName', 'email'] as $field) {
        if (empty($data[$field]) || !is_string($data[$field])) {
            return ['status' => 422, 'body' => ['error' => "Missing required field: {$field}"]];
        }
    }

    $firstName = trim($data['firstName']);
    $lastName = trim($data['lastName']);
    $email = trim(strtolower($data['email']));

    if ($firstName === '' || strlen($firstName) > 100) {
        return ['status' => 422, 'body' => ['error' => 'First name must be between 1 and 100 characters']];
    }

    if ($lastName === '' || strlen($lastName) > 100) {
        return ['status' => 422, 'body' => ['error' => 'Last name must be between 1 and 100 characters']];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 422, 'body' => ['error' => 'Invalid email address']];
    }

    $existing = $findUserByEmail($email);
    if ($existing !== null && (int) $existing['id'] !== (int) $user['id']) {
        return ['status' => 409, 'body' => ['error' => 'An account with this email already exists']];
    }

    $updated = $updateUserProfile($user['id'], $firstName, $lastName, $email);

    return ['status' => 200, 'body' => ['user' => publicUser($updated)]];
}

/**
 * Handles POST /api/change-password: verifies the current password, then
 * sets a new one (and its freshly-assessed strength label).
 * $findUserBySessionTokenHash/$updateUserPassword are injected for the same
 * reason as above.
 *
 * @return array{status: int, body: ?array}
 */
function handleChangePasswordRequest(
    string $method,
    string $rawBody,
    ?string $authHeader,
    callable $findUserBySessionTokenHash,
    callable $updateUserPassword
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $token = bearerTokenFromHeader($authHeader);
    $user = $token ? $findUserBySessionTokenHash(hashSessionToken($token)) : null;

    if ($user === null) {
        return ['status' => 401, 'body' => ['error' => 'Invalid or expired session']];
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data) || empty($data['currentPassword']) || empty($data['newPassword'])) {
        return ['status' => 422, 'body' => ['error' => 'Missing required fields: currentPassword, newPassword']];
    }

    if (!password_verify($data['currentPassword'], $user['password_hash'])) {
        return ['status' => 401, 'body' => ['error' => 'Current password is incorrect']];
    }

    if (!isPasswordAcceptable($data['newPassword'])) {
        return ['status' => 422, 'body' => [
            'error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters and include a letter and a number',
        ]];
    }

    $strength = passwordStrength($data['newPassword']);
    $updateUserPassword($user['id'], password_hash($data['newPassword'], PASSWORD_BCRYPT), $strength['label']);

    return ['status' => 200, 'body' => ['status' => 'updated']];
}
