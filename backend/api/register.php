<?php

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Users.php';
require_once __DIR__ . '/../src/EmailVerifications.php';
require_once __DIR__ . '/../src/AuthEndpoint.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$response = handleRegisterRequest(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input'),
    'findUserByEmail',
    'createUser',
    'replaceEmailVerificationForUser',
    'sendVerificationEmail'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
