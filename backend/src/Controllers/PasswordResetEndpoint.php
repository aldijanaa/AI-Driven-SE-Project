<?php

require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Services/PasswordPolicy.php';

const PASSWORD_RESET_LIFETIME_SECONDS = 60 * 60; // 1 hour

// See sessionExpiryFromNow() in Auth.php for why the '+00' suffix matters.
function passwordResetExpiryFromNow(): string
{
    return gmdate('Y-m-d H:i:s', time() + PASSWORD_RESET_LIFETIME_SECONDS) . '+00';
}

/**
 * Handles POST /api/forgot-password. Always responds with the same generic
 * message whether or not the email belongs to an account, so the endpoint
 * can't be used to discover which emails are registered.
 * $findUserByEmail/$replacePasswordResetForUser/$sendPasswordResetEmail are
 * injected so this can be unit tested without a live database or an
 * outbound webhook call.
 *
 * @return array{status: int, body: ?array}
 */
function handleForgotPasswordRequest(
    string $method,
    string $rawBody,
    callable $findUserByEmail,
    callable $replacePasswordResetForUser,
    callable $sendPasswordResetEmail
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $data = json_decode($rawBody, true);
    $email = is_array($data) && !empty($data['email']) && is_string($data['email'])
        ? trim(strtolower($data['email']))
        : null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 422, 'body' => ['error' => 'Invalid email address']];
    }

    $user = $findUserByEmail($email);

    if ($user !== null) {
        $token = generateSessionToken();
        $replacePasswordResetForUser($user['id'], hashSessionToken($token), passwordResetExpiryFromNow());
        $sendPasswordResetEmail($user['email'], $token);
    }

    return ['status' => 200, 'body' => [
        'message' => "If that email is registered, we've sent a password reset link.",
    ]];
}

/**
 * Handles POST /api/reset-password: consumes a reset token and sets a new
 * password, then signs the user out everywhere else.
 * $findPasswordResetByTokenHash/$updateUserPassword/$deletePasswordReset/
 * $deleteAllSessionsForUser are injected for the same reason as above.
 *
 * @return array{status: int, body: ?array}
 */
function handleResetPasswordRequest(
    string $method,
    string $rawBody,
    callable $findPasswordResetByTokenHash,
    callable $updateUserPassword,
    callable $deletePasswordReset,
    callable $deleteAllSessionsForUser
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data) || empty($data['token']) || empty($data['newPassword'])) {
        return ['status' => 422, 'body' => ['error' => 'Missing required fields: token, newPassword']];
    }

    $reset = $findPasswordResetByTokenHash(hashSessionToken($data['token']));
    if ($reset === null) {
        return ['status' => 400, 'body' => ['error' => 'This reset link is invalid or has expired']];
    }

    if (!isPasswordAcceptable($data['newPassword'])) {
        return ['status' => 422, 'body' => [
            'error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters and include a letter and a number',
        ]];
    }

    $strength = passwordStrength($data['newPassword']);
    $updateUserPassword($reset['user_id'], password_hash($data['newPassword'], PASSWORD_BCRYPT), $strength['label']);
    $deletePasswordReset($reset['id']);
    $deleteAllSessionsForUser($reset['user_id']);

    return ['status' => 200, 'body' => ['status' => 'updated']];
}

/**
 * Best-effort email send via the same n8n-webhook pattern as
 * NotifyEndpoint::sendNotifyWebhook, posted to its own workflow
 * (N8N_PASSWORD_RESET_WEBHOOK_URL) rather than the results-email one, since
 * the template is unrelated. A delivery failure - or the webhook simply not
 * being configured - must never surface to the caller:
 * handleForgotPasswordRequest's response is already fixed regardless.
 */
function sendPasswordResetEmail(string $email, string $token): void
{
    $webhookUrl = getenv('N8N_PASSWORD_RESET_WEBHOOK_URL');
    if (!$webhookUrl) {
        return;
    }

    try {
        $frontendUrl = rtrim(getenv('FRONTEND_URL') ?: 'http://localhost:5173', '/');
        $resetUrl = $frontendUrl . '/?resetToken=' . urlencode($token);

        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'resetUrl' => $resetUrl]),
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ] + curlCaOptions());
        curl_exec($ch);
        curl_close($ch);
    } catch (Throwable $e) {
        // Non-fatal: handleForgotPasswordRequest's response is already fixed.
    }
}
