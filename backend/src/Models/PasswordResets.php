<?php

require_once __DIR__ . '/../../database/Database.php';

/**
 * Issues a fresh reset token for a user, discarding any earlier one first
 * so only the most recently requested link ever works.
 */
function replacePasswordResetForUser(int $userId, string $tokenHash, string $expiresAt): void
{
    $db = getDb();
    $db->prepare('DELETE FROM password_resets WHERE user_id = :user_id')->execute([':user_id' => $userId]);

    $stmt = $db->prepare('
        INSERT INTO password_resets (user_id, token_hash, expires_at)
        VALUES (:user_id, :token_hash, :expires_at)
    ');
    $stmt->execute([':user_id' => $userId, ':token_hash' => $tokenHash, ':expires_at' => $expiresAt]);
}

/**
 * Loads an unexpired reset token by its hash, or null if it doesn't exist,
 * already expired, or was already consumed (rows are deleted on use).
 */
function findPasswordResetByTokenHash(string $tokenHash): ?array
{
    $stmt = getDb()->prepare('
        SELECT * FROM password_resets WHERE token_hash = :token_hash AND expires_at > now()
    ');
    $stmt->execute([':token_hash' => $tokenHash]);

    $reset = $stmt->fetch();
    return $reset === false ? null : $reset;
}

function deletePasswordReset(int $resetId): void
{
    $stmt = getDb()->prepare('DELETE FROM password_resets WHERE id = :id');
    $stmt->execute([':id' => $resetId]);
}
