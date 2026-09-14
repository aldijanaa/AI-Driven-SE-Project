<?php

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Destinations.php';
require_once __DIR__ . '/../src/Matcher.php';
require_once __DIR__ . '/../src/MatchEndpoint.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Users.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

$token = bearerTokenFromHeader($_SERVER['HTTP_AUTHORIZATION'] ?? null);
$currentUser = $token ? findUserBySessionTokenHash(hashSessionToken($token)) : null;

$response = handleMatchRequest(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input'),
    $currentUser['id'] ?? null,
    'getDestinations',
    'matchDestinations',
    'logSubmission'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
