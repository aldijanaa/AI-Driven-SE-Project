<?php

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../src/Destinations.php';
require_once __DIR__ . '/../src/Embeddings.php';
require_once __DIR__ . '/../src/SearchEndpoint.php';

loadEnv(__DIR__ . '/../.env');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$response = handleSearchRequest(
    $_SERVER['REQUEST_METHOD'],
    $_GET['q'] ?? null,
    'embedText',
    'getDestinationsWithEmbeddings'
);

http_response_code($response['status']);
if ($response['body'] !== null) {
    echo json_encode($response['body']);
}
