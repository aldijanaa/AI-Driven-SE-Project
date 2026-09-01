<?php

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'travelmatch';
        $user = getenv('DB_USER') ?: 'travelmatch';
        $password = getenv('DB_PASSWORD') ?: '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
