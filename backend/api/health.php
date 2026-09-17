<?php

require_once __DIR__ . '/../src/Controllers/Health.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

echo json_encode(healthCheck());
