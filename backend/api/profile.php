<?php

require_once __DIR__ . '/../src/Core/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Models/Users.php';
require_once __DIR__ . '/../src/Controllers/ProfileEndpoint.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

$response = handleUpdateProfileRequest(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input'),
    $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'findUserBySessionTokenHash',
    'findUserByEmail',
    'updateUserProfile'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
