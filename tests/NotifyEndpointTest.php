<?php

use PHPUnit\Framework\TestCase;

final class NotifyEndpointTest extends TestCase
{
    private function validBody(array $overrides = []): array
    {
        return array_merge([
            'email' => 'traveler@example.com',
            'results' => [['name' => 'Lisbon', 'match' => 90]],
        ], $overrides);
    }

    private function failingSendWebhook(): callable
    {
        return fn () => $this->fail('sendWebhook should not be called');
    }

    public function testOptionsRequestReturnsNoContent(): void
    {
        $response = handleNotifyRequest('OPTIONS', '', 'https://example.com/hook', $this->failingSendWebhook());

        $this->assertSame(204, $response['status']);
        $this->assertNull($response['body']);
    }

    public function testNonPostMethodIsRejected(): void
    {
        $response = handleNotifyRequest('GET', '', 'https://example.com/hook', $this->failingSendWebhook());

        $this->assertSame(405, $response['status']);
        $this->assertSame('Method not allowed', $response['body']['error']);
    }

    public function testMissingEmailOrResultsIsRejected(): void
    {
        $missingEmail = handleNotifyRequest(
            'POST',
            json_encode($this->validBody(['email' => ''])),
            'https://example.com/hook',
            $this->failingSendWebhook()
        );
        $missingResults = handleNotifyRequest(
            'POST',
            json_encode($this->validBody(['results' => []])),
            'https://example.com/hook',
            $this->failingSendWebhook()
        );

        $this->assertSame(422, $missingEmail['status']);
        $this->assertSame(422, $missingResults['status']);
    }

    public function testInvalidEmailIsRejected(): void
    {
        $response = handleNotifyRequest(
            'POST',
            json_encode($this->validBody(['email' => 'not-an-email'])),
            'https://example.com/hook',
            $this->failingSendWebhook()
        );

        $this->assertSame(422, $response['status']);
        $this->assertSame('Invalid email address', $response['body']['error']);
    }

    public function testMissingWebhookUrlReturnsServiceUnavailable(): void
    {
        $response = handleNotifyRequest(
            'POST',
            json_encode($this->validBody()),
            null,
            $this->failingSendWebhook()
        );

        $this->assertSame(503, $response['status']);
    }

    public function testSuccessfulWebhookCallReturnsSent(): void
    {
        $response = handleNotifyRequest(
            'POST',
            json_encode($this->validBody()),
            'https://example.com/hook',
            fn (string $url, array $payload) => ['response' => '{}', 'status' => 200]
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame(['status' => 'sent'], $response['body']);
    }

    public function testWebhookPassesEmailAndResultsThrough(): void
    {
        $body = $this->validBody();
        $seenUrl = null;
        $seenPayload = null;

        handleNotifyRequest(
            'POST',
            json_encode($body),
            'https://example.com/hook',
            function (string $url, array $payload) use (&$seenUrl, &$seenPayload) {
                $seenUrl = $url;
                $seenPayload = $payload;
                return ['response' => '{}', 'status' => 200];
            }
        );

        $this->assertSame('https://example.com/hook', $seenUrl);
        $this->assertSame($body['email'], $seenPayload['email']);
        $this->assertSame($body['results'], $seenPayload['results']);
    }

    public function testFailedCurlCallReturnsBadGateway(): void
    {
        $response = handleNotifyRequest(
            'POST',
            json_encode($this->validBody()),
            'https://example.com/hook',
            fn () => ['response' => false, 'status' => 0]
        );

        $this->assertSame(502, $response['status']);
    }

    public function testNonSuccessStatusFromWebhookReturnsBadGateway(): void
    {
        $response = handleNotifyRequest(
            'POST',
            json_encode($this->validBody()),
            'https://example.com/hook',
            fn () => ['response' => 'error body', 'status' => 500]
        );

        $this->assertSame(502, $response['status']);
    }
}
