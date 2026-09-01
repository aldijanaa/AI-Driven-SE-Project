<?php

/**
 * Front controller for `php -S`: maps clean paths like /api/match to
 * backend/api/match.php so the .php extension never appears in a URL.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (!preg_match('#^/api/([a-zA-Z0-9_-]+)$#', $path, $matches)) {
    return false;
}

$file = __DIR__ . '/api/' . $matches[1] . '.php';

if (!is_file($file)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found']);
    return true;
}

require $file;
