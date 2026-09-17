<?php

const SESSION_LIFETIME_SECONDS = 60 * 60 * 24 * 30; // 30 days

function generateSessionToken(): string
{
    return bin2hex(random_bytes(32));
}

function hashSessionToken(string $token): string
{
    return hash('sha256', $token);
}

/**
 * The trailing '+00' makes this unambiguously UTC to Postgres. Without it,
 * a timestamptz column parses a naive "Y-m-d H:i:s" string using the DB
 * session's own timezone rather than UTC, silently skewing every expiry by
 * whatever that offset is.
 */
function sessionExpiryFromNow(): string
{
    return gmdate('Y-m-d H:i:s', time() + SESSION_LIFETIME_SECONDS) . '+00';
}

function publicUser(array $user): array
{
    return [
        'id' => $user['id'],
        'firstName' => $user['first_name'],
        'lastName' => $user['last_name'],
        'email' => $user['email'],
        'createdAt' => $user['created_at'] ?? null,
    ];
}

function bearerTokenFromHeader(?string $header): ?string
{
    if ($header && preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
        return $matches[1];
    }

    return null;
}
