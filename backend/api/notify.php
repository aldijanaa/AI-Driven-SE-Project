<?php

require_once __DIR__ . '/../src/Core/Env.php';
require_once __DIR__ . '/../src/Controllers/NotifyEndpoint.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$response = handleNotifyRequest(
    $_SERVER['REQUEST_METHOD'],
    file_get_contents('php://input'),
    getenv('N8N_WEBHOOK_URL') ?: null,
    'sendNotifyWebhook'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
