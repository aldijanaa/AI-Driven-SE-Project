<?php

use PHPUnit\Framework\TestCase;

final class EmailVerificationEndpointTest extends TestCase
{
    private function failIfCalled(string $name): callable
    {
        return fn (...$args) => $this->fail("{$name} should not be called");
    }

    private function fakeVerification(string $code, array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'user_id' => 9,
            'code_hash' => hashVerificationCode($code),
            'attempts' => 0,
        ], $overrides);
    }

    // --- handleVerifyEmailRequest ---

    public function testVerifyOptionsReturnsNoContent(): void
    {
        $response = handleVerifyEmailRequest(
            'OPTIONS',
            '',
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('findEmailVerificationByUserId'),
            $this->failIfCalled('incrementEmailVerificationAttempts'),
            $this->failIfCalled('deleteEmailVerification'),
            $this->failIfCalled('markUserEmailVerified'),
            $this->failIfCalled('createSession')
        );

        $this->assertSame(204, $response['status']);
    }

    public function testVerifyNonPostIsRejected(): void
    {
        $response = handleVerifyEmailRequest(
            'GET',
            '',
            fn () => null,
            fn () => null,
            fn () => null,
            fn () => null,
            fn () => null,
            fn () => null
        );

        $this->assertSame(405, $response['status']);
    }

    public function testVerifyMissingFieldsAreRejected(): void
    {
        $response = handleVerifyEmailRequest(
            'POST',
            json_encode(['email' => 'ada@example.com']),
            $this->failIfCalled('findUserByEmail'),
            fn () => null,
            fn () => null,
            fn () => null,
            fn () => null,
            fn () => null
        );

        $this->assertSame(422, $response['status']);
    }

    public function testVerifyUnknownEmailIsRejected(): void
    {
        $response = handleVerifyEmailRequest(
            'POST',
            json_encode(['email' => 'nobody@example.com', 'code' => '123456']),
            fn () => null,
            $this->failIfCalled('findEmailVerificationByUserId'),
            $this->failIfCalled('incrementEmailVerificationAttempts'),
            $this->failIfCalled('deleteEmailVerification'),
            $this->failIfCalled('markUserEmailVerified'),
            $this->failIfCalled('createSession')
        );

        $this->assertSame(400, $response['status']);
    }

    public function testVerifyNoOutstandingCodeIsRejected(): void
    {
        $response = handleVerifyEmailRequest(
            'POST',
            json_encode(['email' => 'ada@example.com', 'code' => '123456']),
            fn () => ['id' => 9, 'email' => 'ada@example.com'],
            fn () => null,
            $this->failIfCalled('incrementEmailVerificationAttempts'),
            $this->failIfCalled('deleteEmailVerification'),
            $this->failIfCalled('markUserEmailVerified'),
            $this->failIfCalled('createSession')
        );

        $this->assertSame(400, $response['status']);
    }

    public function testVerifyWrongCodeIncrementsAttempts(): void
    {
        $incrementedId = null;

        $response = handleVerifyEmailRequest(
            'POST',
            json_encode(['email' => 'ada@example.com', 'code' => '000000']),
            fn () => ['id' => 9, 'email' => 'ada@example.com'],
            fn () => $this->fakeVerification('111111'),
            function (int $id) use (&$incrementedId) {
                $incrementedId = $id;
            },
            $this->failIfCalled('deleteEmailVerification'),
            $this->failIfCalled('markUserEmailVerified'),
            $this->failIfCalled('createSession')
        );

        $this->assertSame(400, $response['status']);
        $this->assertSame(1, $incrementedId);
    }

    public function testVerifyTooManyAttemptsIsRejected(): void
    {
        $response = handleVerifyEmailRequest(
            'POST',
            json_encode(['email' => 'ada@example.com', 'code' => '111111']),
            fn () => ['id' => 9, 'email' => 'ada@example.com'],
            fn () => $this->fakeVerification('111111', ['attempts' => 5]),
            $this->failIfCalled('incrementEmailVerificationAttempts'),
            $this->failIfCalled('deleteEmailVerification'),
            $this->failIfCalled('markUserEmailVerified'),
            $this->failIfCalled('createSession')
        );

        $this->assertSame(429, $response['status']);
    }

    public function testVerifyCorrectCodeMarksVerifiedAndCreatesSession(): void
    {
        $markedUserId = null;
        $deletedVerificationId = null;
        $sessionArgs = null;

        $response = handleVerifyEmailRequest(
            'POST',
            json_encode(['email' => 'ada@example.com', 'code' => '111111']),
            fn () => ['id' => 9, 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            fn () => $this->fakeVerification('111111'),
            $this->failIfCalled('incrementEmailVerificationAttempts'),
            function (int $id) use (&$deletedVerificationId) {
                $deletedVerificationId = $id;
            },
            function (int $userId) use (&$markedUserId) {
                $markedUserId = $userId;
            },
            function (...$args) use (&$sessionArgs) {
                $sessionArgs = $args;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame('Ada', $response['body']['user']['firstName']);
        $this->assertNotEmpty($response['body']['token']);
        $this->assertSame(9, $markedUserId);
        $this->assertSame(1, $deletedVerificationId);
        $this->assertSame(9, $sessionArgs[0]);
    }

    // --- handleResendVerificationCodeRequest ---

    public function testResendInvalidEmailIsRejected(): void
    {
        $response = handleResendVerificationCodeRequest(
            'POST',
            json_encode(['email' => 'not-an-email']),
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('replaceEmailVerificationForUser'),
            $this->failIfCalled('sendVerificationEmail')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testResendForAlreadyVerifiedAccountIsANoOp(): void
    {
        $response = handleResendVerificationCodeRequest(
            'POST',
            json_encode(['email' => 'ada@example.com']),
            fn () => ['id' => 9, 'email' => 'ada@example.com', 'email_verified' => true],
            $this->failIfCalled('replaceEmailVerificationForUser'),
            $this->failIfCalled('sendVerificationEmail')
        );

        $this->assertSame(200, $response['status']);
    }

    public function testResendForUnverifiedAccountSendsNewCode(): void
    {
        $replaceArgs = null;
        $sendArgs = null;

        $response = handleResendVerificationCodeRequest(
            'POST',
            json_encode(['email' => 'ada@example.com']),
            fn () => ['id' => 9, 'email' => 'ada@example.com', 'email_verified' => false],
            function (...$args) use (&$replaceArgs) {
                $replaceArgs = $args;
            },
            function (...$args) use (&$sendArgs) {
                $sendArgs = $args;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame(9, $replaceArgs[0]);
        $this->assertSame('ada@example.com', $sendArgs[0]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sendArgs[1]);
    }
}
