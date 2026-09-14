<?php

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Users.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/HistoryEndpoint.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

$response = handleHistoryRequest(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'findUserBySessionTokenHash',
    'findSubmissionsByUserId'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
