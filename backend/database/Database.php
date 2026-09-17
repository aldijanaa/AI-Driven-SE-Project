<?php

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        // Hosted Postgres (Render, Heroku, etc.) exposes a single DATABASE_URL
        // connection string, so prefer parsing that when present; local dev
        // and any other host keep using the discrete DB_* vars below.
        $databaseUrl = getenv('DATABASE_URL') ?: null;

        if ($databaseUrl) {
            $parts = parse_url($databaseUrl);
            $host = $parts['host'];
            $port = $parts['port'] ?? '5432';
            $name = ltrim($parts['path'] ?? '', '/');
            $user = $parts['user'] ?? '';
            $password = $parts['pass'] ?? '';
            $sslmode = 'require';
        } else {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'travelmatch';
            $user = getenv('DB_USER') ?: 'travelmatch';
            $password = getenv('DB_PASSWORD') ?: '';
            $sslmode = getenv('DB_SSLMODE') ?: null;
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        if ($sslmode) {
            $dsn .= ";sslmode={$sslmode}";
        }

        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
