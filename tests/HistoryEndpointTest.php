<?php

use PHPUnit\Framework\TestCase;

final class HistoryEndpointTest extends TestCase
{
    public function testOptionsRequestReturnsNoContent(): void
    {
        $response = handleHistoryRequest(
            'OPTIONS',
            null,
            fn () => $this->fail('findUserBySessionTokenHash should not be called for OPTIONS'),
            fn () => $this->fail('findSubmissionsByUserId should not be called for OPTIONS')
        );

        $this->assertSame(204, $response['status']);
    }

    public function testNonGetMethodIsRejected(): void
    {
        $response = handleHistoryRequest('POST', null, fn () => null, fn () => []);

        $this->assertSame(405, $response['status']);
    }

    public function testMissingAuthHeaderIsRejected(): void
    {
        $response = handleHistoryRequest(
            'GET',
            null,
            fn () => $this->fail('findUserBySessionTokenHash should not be called without a token'),
            fn () => $this->fail('findSubmissionsByUserId should not be called without a token')
        );

        $this->assertSame(401, $response['status']);
    }

    public function testInvalidTokenIsRejected(): void
    {
        $response = handleHistoryRequest(
            'GET',
            'Bearer bad-token',
            fn () => null,
            fn () => $this->fail('findSubmissionsByUserId should not be called for an invalid token')
        );

        $this->assertSame(401, $response['status']);
    }

    public function testValidTokenReturnsThatUsersSubmissions(): void
    {
        $fakeSubmissions = [['id' => 1, 'results' => []]];
        $lookedUpUserId = null;

        $response = handleHistoryRequest(
            'GET',
            'Bearer good-token',
            fn () => ['id' => 9, 'first_name' => 'Ada'],
            function (int $userId) use ($fakeSubmissions, &$lookedUpUserId) {
                $lookedUpUserId = $userId;
                return $fakeSubmissions;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame($fakeSubmissions, $response['body']['submissions']);
        $this->assertSame(9, $lookedUpUserId);
    }
}
