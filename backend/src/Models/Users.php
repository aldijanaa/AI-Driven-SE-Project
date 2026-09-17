<?php

require_once __DIR__ . '/../../database/Database.php';

/**
 * Loads a user by email, or null if no account uses it. See
 * database/migrations/001_create_users_table.sql for the table definition.
 */
function findUserByEmail(string $email): ?array
{
    $stmt = getDb()->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);

    $user = $stmt->fetch();
    return $user === false ? null : $user;
}

/**
 * New accounts start unverified - a session is only ever issued once the
 * emailed code is confirmed (see EmailVerificationEndpoint.php).
 */
function createUser(string $firstName, string $lastName, string $email, string $passwordHash, string $passwordStrength): array
{
    $stmt = getDb()->prepare('
        INSERT INTO users (first_name, last_name, email, password_hash, password_strength, email_verified)
        VALUES (:first_name, :last_name, :email, :password_hash, :password_strength, false)
        RETURNING *
    ');

    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':password_strength' => $passwordStrength,
    ]);

    return $stmt->fetch();
}

function updateUserProfile(int $userId, string $firstName, string $lastName, string $email): array
{
    $stmt = getDb()->prepare('
        UPDATE users
        SET first_name = :first_name, last_name = :last_name, email = :email
        WHERE id = :id
        RETURNING *
    ');

    $stmt->execute([
        ':id' => $userId,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
    ]);

    return $stmt->fetch();
}

function updateUserPassword(int $userId, string $passwordHash, string $passwordStrength): void
{
    $stmt = getDb()->prepare('
        UPDATE users SET password_hash = :password_hash, password_strength = :password_strength
        WHERE id = :id
    ');

    $stmt->execute([
        ':id' => $userId,
        ':password_hash' => $passwordHash,
        ':password_strength' => $passwordStrength,
    ]);
}

/**
 * Logs a user out of every device/session at once - used after a password
 * reset, since a forgotten-or-compromised password should invalidate
 * whatever sessions were riding on it.
 */
function deleteAllSessionsForUser(int $userId): void
{
    $stmt = getDb()->prepare('DELETE FROM user_sessions WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
}

function createSession(int $userId, string $tokenHash, string $expiresAt): void
{
    $stmt = getDb()->prepare('
        INSERT INTO user_sessions (user_id, token_hash, expires_at)
        VALUES (:user_id, :token_hash, :expires_at)
    ');

    $stmt->execute([
        ':user_id' => $userId,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt,
    ]);
}

/**
 * Loads the user behind a (hashed) session token, or null if the token is
 * unknown or has expired.
 */
function findUserBySessionTokenHash(string $tokenHash): ?array
{
    $stmt = getDb()->prepare('
        SELECT u.*
        FROM user_sessions s
        JOIN users u ON u.id = s.user_id
        WHERE s.token_hash = :token_hash AND s.expires_at > now()
    ');
    $stmt->execute([':token_hash' => $tokenHash]);

    $user = $stmt->fetch();
    return $user === false ? null : $user;
}

function deleteSessionByTokenHash(string $tokenHash): void
{
    $stmt = getDb()->prepare('DELETE FROM user_sessions WHERE token_hash = :token_hash');
    $stmt->execute([':token_hash' => $tokenHash]);
}

/**
 * Loads a user's past quiz submissions, most recent first, for their
 * history page. `results` holds the full match list shown at the time
 * (see MatchEndpoint::logSubmission), not just the top pick.
 */
function findSubmissionsByUserId(int $userId, int $limit = 50): array
{
    $stmt = getDb()->prepare('
        SELECT id, duration, interests, weather, companions, style,
               budget_level, budget_amount, getaway, season, results, created_at
        FROM quiz_submissions
        WHERE user_id = :user_id
        ORDER BY created_at DESC
        LIMIT :limit
    ');
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return array_map(function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'createdAt' => $row['created_at'],
            'duration' => $row['duration'],
            'interests' => parsePgTextArray($row['interests']),
            'weather' => $row['weather'],
            'companions' => $row['companions'],
            'style' => $row['style'],
            'budgetLevel' => $row['budget_level'],
            'budgetAmount' => $row['budget_amount'] !== null ? (float) $row['budget_amount'] : null,
            'getaway' => $row['getaway'],
            'season' => $row['season'],
            'results' => json_decode($row['results'], true) ?? [],
        ];
    }, $stmt->fetchAll());
}

/**
 * Deletes one quiz submission from a user's history, scoped to that user so
 * nobody can delete another user's entry by guessing an id. Returns whether
 * a row was actually deleted.
 */
function deleteSubmissionByUserId(int $userId, int $submissionId): bool
{
    $stmt = getDb()->prepare('
        DELETE FROM quiz_submissions WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([':id' => $submissionId, ':user_id' => $userId]);

    return $stmt->rowCount() > 0;
}

/**
 * Parses a Postgres text[] literal like '{"food","beach"}' back into a PHP
 * array. Values here are always simple tag words with no embedded commas
 * or braces, so a straight split covers it.
 */
function parsePgTextArray(?string $raw): array
{
    if ($raw === null || $raw === '{}') {
        return [];
    }

    $inner = trim($raw, '{}');
    return array_map(
        fn ($v) => trim($v, '"'),
        str_getcsv($inner)
    );
}
