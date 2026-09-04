<?php

require_once __DIR__ . '/../src/Health.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

echo json_encode(healthCheck());
