<?php

const PASSWORD_MIN_LENGTH = 8;

/**
 * Scores a password 0-4 on length + character variety and maps it to a
 * label. Mirrors the heuristic used by the frontend's live strength meter
 * (frontend/src/lib/passwordStrength.js) so the number a user sees while
 * typing matches what gets stored on their account.
 */
function passwordStrength(string $password): array
{
    $length = strlen($password);
    $varietyCount = 0;
    foreach (['/[a-z]/', '/[A-Z]/', '/[0-9]/', '/[^a-zA-Z0-9]/'] as $pattern) {
        if (preg_match($pattern, $password)) {
            $varietyCount++;
        }
    }

    $score = 0;
    if ($length >= PASSWORD_MIN_LENGTH) {
        $score++;
    }
    if ($length >= 12) {
        $score++;
    }
    if ($varietyCount >= 3) {
        $score++;
    }
    if ($length >= 16 && $varietyCount >= 3) {
        $score++;
    }

    $label = match (true) {
        $score >= 4 => 'strong',
        $score >= 3 => 'good',
        $score >= 2 => 'fair',
        default => 'weak',
    };

    return ['score' => $score, 'label' => $label];
}

/**
 * Minimum bar to create an account: long enough and not trivially uniform.
 * Anything past this is communicated to the user as a strength hint, not
 * enforced - we'd rather not lock people out of their own chosen passwords.
 */
function isPasswordAcceptable(string $password): bool
{
    return strlen($password) >= PASSWORD_MIN_LENGTH
        && preg_match('/[a-zA-Z]/', $password) === 1
        && preg_match('/[0-9]/', $password) === 1;
}
