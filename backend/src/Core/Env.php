<?php

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
        }
    }
}

/**
 * backend/cacert.pem is an optional, gitignored CA bundle some local dev
 * setups (e.g. PHP on Windows without a system CA store) need for curl to
 * verify HTTPS. Servers with a real system CA store (any Linux deploy,
 * including the Docker image used on Render) don't need or have it, so
 * this only adds CURLOPT_CAINFO when the file actually exists.
 */
function curlCaOptions(): array
{
    $cacert = __DIR__ . '/../../cacert.pem';

    return is_file($cacert) ? [CURLOPT_CAINFO => $cacert] : [];
}
