<?php

require_once __DIR__ . '/../src/Core/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Models/Users.php';
require_once __DIR__ . '/../src/Models/EmailVerifications.php';
require_once __DIR__ . '/../src/Controllers/EmailVerificationEndpoint.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$response = handleResendVerificationCodeRequest(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input'),
    'findUserByEmail',
    'replaceEmailVerificationForUser',
    'sendVerificationEmail'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
