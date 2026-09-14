<?php

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Users.php';
require_once __DIR__ . '/../src/PasswordResets.php';
require_once __DIR__ . '/../src/PasswordResetEndpoint.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$response = handleResetPasswordRequest(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input'),
    'findPasswordResetByTokenHash',
    'updateUserPassword',
    'deletePasswordReset',
    'deleteAllSessionsForUser'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
