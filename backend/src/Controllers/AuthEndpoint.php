<?php

require_once __DIR__ . '/../Services/PasswordPolicy.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/EmailVerificationEndpoint.php';

const AUTH_GENERIC_LOGIN_ERROR = 'Invalid email or password';

/**
 * Handles POST /api/register. Creates the account and emails a 6-digit
 * verification code, but does NOT log the user in - a session is only
 * issued once that code is confirmed via /api/verify-email.
 * $findUserByEmail/$createUser/$replaceEmailVerificationForUser/
 * $sendVerificationEmail are injected so this can be unit tested without a
 * live database or outbound webhook call.
 *
 * @return array{status: int, body: ?array}
 */
function handleRegisterRequest(
    string $method,
    string $rawBody,
    callable $findUserByEmail,
    callable $createUser,
    callable $replaceEmailVerificationForUser,
    callable $sendVerificationEmail
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        return ['status' => 400, 'body' => ['error' => 'Invalid JSON body']];
    }

    foreach (['firstName', 'lastName', 'email', 'password'] as $field) {
        if (empty($data[$field]) || !is_string($data[$field])) {
            return ['status' => 422, 'body' => ['error' => "Missing required field: {$field}"]];
        }
    }

    $firstName = trim($data['firstName']);
    $lastName = trim($data['lastName']);
    $email = trim(strtolower($data['email']));
    $password = $data['password'];

    if ($firstName === '' || strlen($firstName) > 100) {
        return ['status' => 422, 'body' => ['error' => 'First name must be between 1 and 100 characters']];
    }

    if ($lastName === '' || strlen($lastName) > 100) {
        return ['status' => 422, 'body' => ['error' => 'Last name must be between 1 and 100 characters']];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 422, 'body' => ['error' => 'Invalid email address']];
    }

    if (!isPasswordAcceptable($password)) {
        return ['status' => 422, 'body' => [
            'error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters and include a letter and a number',
        ]];
    }

    if ($findUserByEmail($email) !== null) {
        return ['status' => 409, 'body' => ['error' => 'An account with this email already exists']];
    }

    $strength = passwordStrength($password);
    $user = $createUser(
        $firstName,
        $lastName,
        $email,
        password_hash($password, PASSWORD_BCRYPT),
        $strength['label']
    );

    $code = generateVerificationCode();
    $replaceEmailVerificationForUser($user['id'], hashVerificationCode($code), verificationExpiryFromNow());
    $sendVerificationEmail($user['email'], $code);

    return ['status' => 201, 'body' => ['user' => publicUser($user), 'needsVerification' => true]];
}

/**
 * Handles POST /api/login.
 *
 * @return array{status: int, body: ?array}
 */
function handleLoginRequest(
    string $method,
    string $rawBody,
    callable $findUserByEmail,
    callable $createSession
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data) || empty($data['email']) || empty($data['password'])) {
        return ['status' => 422, 'body' => ['error' => 'Missing required fields: email, password']];
    }

    $email = trim(strtolower($data['email']));
    $user = $findUserByEmail($email);

    if ($user === null || !password_verify($data['password'], $user['password_hash'])) {
        return ['status' => 401, 'body' => ['error' => AUTH_GENERIC_LOGIN_ERROR]];
    }

    if (empty($user['email_verified'])) {
        return ['status' => 403, 'body' => [
            'error' => 'Please verify your email first',
            'needsVerification' => true,
            'email' => $user['email'],
        ]];
    }

    $token = generateSessionToken();
    $createSession($user['id'], hashSessionToken($token), sessionExpiryFromNow());

    return ['status' => 200, 'body' => ['user' => publicUser($user), 'token' => $token]];
}

/**
 * Handles GET /api/me: resolves the bearer token to its user, letting the
 * frontend restore a session after a page reload.
 *
 * @return array{status: int, body: ?array}
 */
function handleMeRequest(string $method, ?string $authHeader, callable $findUserBySessionTokenHash): array
{
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

    return ['status' => 200, 'body' => ['user' => publicUser($user)]];
}

/**
 * Handles POST /api/logout: best-effort deletion of the session so the
 * token can no longer be used, regardless of whether it was still valid.
 *
 * @return array{status: int, body: ?array}
 */
function handleLogoutRequest(string $method, ?string $authHeader, callable $deleteSessionByTokenHash): array
{
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $token = bearerTokenFromHeader($authHeader);
    if ($token) {
        $deleteSessionByTokenHash(hashSessionToken($token));
    }

    return ['status' => 204, 'body' => null];
}
