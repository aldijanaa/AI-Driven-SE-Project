<?php

require_once __DIR__ . '/../src/Env.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!is_array($body) || empty($body['email']) || empty($body['results'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Missing required fields: email, results']);
    exit;
}

if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

$webhookUrl = getenv('N8N_WEBHOOK_URL');
if (!$webhookUrl) {
    http_response_code(503);
    echo json_encode(['error' => 'Email notifications are not configured yet']);
    exit;
}

$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'email' => $body['email'],
        'results' => $body['results'],
    ]),
    CURLOPT_TIMEOUT => 8,
    CURLOPT_CAINFO => __DIR__ . '/../cacert.pem',
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $status >= 300) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to reach the notification workflow']);
    exit;
}

echo json_encode(['status' => 'sent']);
