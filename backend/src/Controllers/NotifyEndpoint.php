<?php

/**
 * Handles a request to POST /api/notify: validates it, then hands the
 * payload off to $sendWebhook. $sendWebhook is injected so this can be unit
 * tested without a real network call - production passes sendNotifyWebhook().
 *
 * @return array{status: int, body: ?array}
 */
function handleNotifyRequest(string $method, string $rawBody, ?string $webhookUrl, callable $sendWebhook): array
{
    if ($method === 'OPTIONS') {
        return ['status' => 204, 'body' => null];
    }

    if ($method !== 'POST') {
        return ['status' => 405, 'body' => ['error' => 'Method not allowed']];
    }

    $body = json_decode($rawBody, true);

    if (!is_array($body) || empty($body['email']) || empty($body['results'])) {
        return ['status' => 422, 'body' => ['error' => 'Missing required fields: email, results']];
    }

    if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
        return ['status' => 422, 'body' => ['error' => 'Invalid email address']];
    }

    if (!$webhookUrl) {
        return ['status' => 503, 'body' => ['error' => 'Email notifications are not configured yet']];
    }

    $result = $sendWebhook($webhookUrl, [
        'email' => $body['email'],
        'results' => $body['results'],
    ]);

    if ($result['response'] === false || $result['status'] >= 300) {
        return ['status' => 502, 'body' => ['error' => 'Failed to reach the notification workflow']];
    }

    return ['status' => 200, 'body' => ['status' => 'sent']];
}

/**
 * @return array{response: string|false, status: int}
 */
function sendNotifyWebhook(string $webhookUrl, array $payload): array
{
    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ] + curlCaOptions());

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['response' => $response, 'status' => $status];
}
