<?php

require_once __DIR__ . '/Auth.php';

const VERIFICATION_CODE_LIFETIME_SECONDS = 60 * 15; // 15 minutes
const VERIFICATION_MAX_ATTEMPTS = 5;

// See sessionExpiryFromNow() in Auth.php for why the '+00' suffix matters.
function verificationExpiryFromNow(): string
{
    return gmdate('Y-m-d H:i:s', time() + VERIFICATION_CODE_LIFETIME_SECONDS) . '+00';
}

function generateVerificationCode(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function hashVerificationCode(string $code): string
{
    return hash('sha256', $code);
}

/**
 * Handles POST /api/verify-email: confirms a 6-digit code and, only on
 * success, creates the user's first session - registration itself never
 * issues one. $findUserByEmail/$findEmailVerificationByUserId/
 * $incrementEmailVerificationAttempts/$deleteEmailVerification/
 * $markUserEmailVerified/$createSession are injected so this can be unit
 * tested without a live database.
 *
 * @return array{status: int, body: ?array}
 */
function handleVerifyEmailRequest(
    string $method,
    string $rawBody,
    callable $findUserByEmail,
    callable $findEmailVerificationByUserId,
    callable $incrementEmailVerificationAttempts,
    callable $deleteEmailVerification,
    callable $markUserEmailVerified,
    callable $createSession
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $data = json_decode($rawBody, true);
    if (!is_array($data) || empty($data['email']) || empty($data['code'])) {
        return ['status' => 422, 'body' => ['error' => 'Missing required fields: email, code']];
    }

    $email = trim(strtolower($data['email']));
    $user = $findUserByEmail($email);
    $verification = $user ? $findEmailVerificationByUserId($user['id']) : null;

    if ($user === null || $verification === null) {
        return ['status' => 400, 'body' => ['error' => 'Invalid or expired verification code']];
    }

    if ($verification['attempts'] >= VERIFICATION_MAX_ATTEMPTS) {
        return ['status' => 429, 'body' => ['error' => 'Too many attempts. Request a new code.']];
    }

    if (!hash_equals($verification['code_hash'], hashVerificationCode((string) $data['code']))) {
        $incrementEmailVerificationAttempts($verification['id']);
        return ['status' => 400, 'body' => ['error' => 'Invalid or expired verification code']];
    }

    $markUserEmailVerified($user['id']);
    $deleteEmailVerification($verification['id']);

    $token = generateSessionToken();
    $createSession($user['id'], hashSessionToken($token), sessionExpiryFromNow());

    return ['status' => 200, 'body' => ['user' => publicUser($user), 'token' => $token]];
}

/**
 * Handles POST /api/resend-verification-code. Same enumeration-safe
 * generic response as forgot-password, and a no-op for accounts that are
 * already verified.
 *
 * @return array{status: int, body: ?array}
 */
function handleResendVerificationCodeRequest(
    string $method,
    string $rawBody,
    callable $findUserByEmail,
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
    $email = is_array($data) && !empty($data['email']) && is_string($data['email'])
        ? trim(strtolower($data['email']))
        : null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 422, 'body' => ['error' => 'Invalid email address']];
    }

    $user = $findUserByEmail($email);

    if ($user !== null && !$user['email_verified']) {
        $code = generateVerificationCode();
        $replaceEmailVerificationForUser($user['id'], hashVerificationCode($code), verificationExpiryFromNow());
        $sendVerificationEmail($user['email'], $code);
    }

    return ['status' => 200, 'body' => [
        'message' => "If that email needs verifying, we've sent a new code.",
    ]];
}

/**
 * Best-effort email send via its own n8n webhook, mirroring
 * PasswordResetEndpoint::sendPasswordResetEmail - a delivery failure (or
 * the webhook not being configured) must never surface to the caller.
 */
function sendVerificationEmail(string $email, string $code): void
{
    $webhookUrl = getenv('N8N_VERIFICATION_WEBHOOK_URL');
    if (!$webhookUrl) {
        return;
    }

    try {
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'code' => $code]),
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CAINFO => __DIR__ . '/../cacert.pem',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        curl_exec($ch);
        curl_close($ch);
    } catch (Throwable $e) {
        // Non-fatal: the caller's response is already fixed.
    }
}
