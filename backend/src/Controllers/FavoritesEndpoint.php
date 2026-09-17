<?php

require_once __DIR__ . '/../Core/Auth.php';

/**
 * Handles both verbs on /api/favorites: GET lists the logged-in user's
 * saved destinations (for the Favorites page); POST toggles one destination
 * on/off (the heart button on any suggestion). $findUserBySessionTokenHash/
 * $findFavoriteDestinationsByUserId/$isFavorited/$addFavorite/
 * $removeFavorite are injected so this can be unit tested without a live
 * database - callers in production pass the real Favorites.php functions.
 *
 * @return array{status: int, body: ?array}
 */
function handleFavoritesRequest(
    string $method,
    string $rawBody,
    ?string $authHeader,
    callable $findUserBySessionTokenHash,
    callable $findFavoriteDestinationsByUserId,
    callable $isFavorited,
    callable $addFavorite,
    callable $removeFavorite
): array {
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'GET' && $method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $token = bearerTokenFromHeader($authHeader);
    $user = $token ? $findUserBySessionTokenHash(hashSessionToken($token)) : null;

    if ($user === null) {
        return ['status' => 401, 'body' => ['error' => 'Invalid or expired session']];
    }

    if ($method === 'GET') {
        return ['status' => 200, 'body' => ['destinations' => $findFavoriteDestinationsByUserId($user['id'])]];
    }

    $data = json_decode($rawBody, true);
    $destinationId = filter_var($data['destinationId'] ?? null, FILTER_VALIDATE_INT);

    if ($destinationId === false) {
        return ['status' => 422, 'body' => ['error' => 'Missing required field: destinationId']];
    }

    if ($isFavorited($user['id'], $destinationId)) {
        $removeFavorite($user['id'], $destinationId);
        return ['status' => 200, 'body' => ['favorited' => false]];
    }

    $addFavorite($user['id'], $destinationId);
    return ['status' => 200, 'body' => ['favorited' => true]];
}
