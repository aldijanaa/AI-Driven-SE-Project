<?php

require_once __DIR__ . '/../database/Database.php';

/**
 * Issues a fresh verification code for a user, discarding any earlier one
 * first so only the most recently sent code ever works.
 */
function replaceEmailVerificationForUser(int $userId, string $codeHash, string $expiresAt): void
{
    $db = getDb();
    $db->prepare('DELETE FROM email_verifications WHERE user_id = :user_id')->execute([':user_id' => $userId]);

    $stmt = $db->prepare('
        INSERT INTO email_verifications (user_id, code_hash, expires_at)
        VALUES (:user_id, :code_hash, :expires_at)
    ');
    $stmt->execute([':user_id' => $userId, ':code_hash' => $codeHash, ':expires_at' => $expiresAt]);
}

/**
 * Loads a user's unexpired verification code (with its attempt count), or
 * null if none is outstanding.
 */
function findEmailVerificationByUserId(int $userId): ?array
{
    $stmt = getDb()->prepare('
        SELECT * FROM email_verifications WHERE user_id = :user_id AND expires_at > now()
    ');
    $stmt->execute([':user_id' => $userId]);

    $verification = $stmt->fetch();
    return $verification === false ? null : $verification;
}

function incrementEmailVerificationAttempts(int $verificationId): void
{
    $stmt = getDb()->prepare('UPDATE email_verifications SET attempts = attempts + 1 WHERE id = :id');
    $stmt->execute([':id' => $verificationId]);
}

function deleteEmailVerification(int $verificationId): void
{
    $stmt = getDb()->prepare('DELETE FROM email_verifications WHERE id = :id');
    $stmt->execute([':id' => $verificationId]);
}

function markUserEmailVerified(int $userId): void
{
    $stmt = getDb()->prepare('UPDATE users SET email_verified = true WHERE id = :id');
    $stmt->execute([':id' => $userId]);
}
