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

    public function testDeleteMissingAuthHeaderIsRejected(): void
    {
        $response = handleHistoryRequest(
            'DELETE',
            null,
            fn () => $this->fail('findUserBySessionTokenHash should not be called without a token'),
            fn () => [],
            '1',
            fn () => $this->fail('deleteSubmissionByUserId should not be called without a token')
        );

        $this->assertSame(401, $response['status']);
    }

    public function testDeleteWithoutIdIsRejected(): void
    {
        $response = handleHistoryRequest(
            'DELETE',
            'Bearer good-token',
            fn () => ['id' => 9, 'first_name' => 'Ada'],
            fn () => [],
            null,
            fn () => $this->fail('deleteSubmissionByUserId should not be called without an id')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testDeleteOfMissingOrUnownedSubmissionReturnsNotFound(): void
    {
        $response = handleHistoryRequest(
            'DELETE',
            'Bearer good-token',
            fn () => ['id' => 9, 'first_name' => 'Ada'],
            fn () => [],
            '42',
            fn () => false
        );

        $this->assertSame(404, $response['status']);
    }

    public function testDeleteRemovesThatUsersSubmission(): void
    {
        $deletedArgs = null;

        $response = handleHistoryRequest(
            'DELETE',
            'Bearer good-token',
            fn () => ['id' => 9, 'first_name' => 'Ada'],
            fn () => [],
            '42',
            function (int $userId, int $submissionId) use (&$deletedArgs) {
                $deletedArgs = [$userId, $submissionId];
                return true;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['body']['deleted']);
        $this->assertSame([9, 42], $deletedArgs);
    }
}
