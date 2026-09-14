<?php

use PHPUnit\Framework\TestCase;

final class PasswordResetEndpointTest extends TestCase
{
    private function failIfCalled(string $name): callable
    {
        return fn (...$args) => $this->fail("{$name} should not be called");
    }

    // --- handleForgotPasswordRequest ---

    public function testForgotPasswordOptionsReturnsNoContent(): void
    {
        $response = handleForgotPasswordRequest(
            'OPTIONS',
            '',
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('replacePasswordResetForUser'),
            $this->failIfCalled('sendPasswordResetEmail')
        );

        $this->assertSame(204, $response['status']);
    }

    public function testForgotPasswordNonPostIsRejected(): void
    {
        $response = handleForgotPasswordRequest('GET', '', fn () => null, fn () => null, fn () => null);

        $this->assertSame(405, $response['status']);
    }

    public function testForgotPasswordInvalidEmailIsRejected(): void
    {
        $response = handleForgotPasswordRequest(
            'POST',
            json_encode(['email' => 'not-an-email']),
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('replacePasswordResetForUser'),
            $this->failIfCalled('sendPasswordResetEmail')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testForgotPasswordUnknownEmailReturnsGenericSuccessWithoutSideEffects(): void
    {
        $response = handleForgotPasswordRequest(
            'POST',
            json_encode(['email' => 'nobody@example.com']),
            fn () => null,
            $this->failIfCalled('replacePasswordResetForUser'),
            $this->failIfCalled('sendPasswordResetEmail')
        );

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('If that email', $response['body']['message']);
    }

    public function testForgotPasswordKnownEmailCreatesResetAndSendsEmail(): void
    {
        $replaceArgs = null;
        $sendArgs = null;

        $response = handleForgotPasswordRequest(
            'POST',
            json_encode(['email' => 'ada@example.com']),
            fn () => ['id' => 9, 'email' => 'ada@example.com'],
            function (...$args) use (&$replaceArgs) {
                $replaceArgs = $args;
            },
            function (...$args) use (&$sendArgs) {
                $sendArgs = $args;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('If that email', $response['body']['message']);
        $this->assertSame(9, $replaceArgs[0]);
        $this->assertSame(64, strlen($replaceArgs[1]));
        $this->assertSame('ada@example.com', $sendArgs[0]);
        $this->assertNotEmpty($sendArgs[1]);
    }

    // --- handleResetPasswordRequest ---

    public function testResetPasswordOptionsReturnsNoContent(): void
    {
        $response = handleResetPasswordRequest(
            'OPTIONS',
            '',
            $this->failIfCalled('findPasswordResetByTokenHash'),
            $this->failIfCalled('updateUserPassword'),
            $this->failIfCalled('deletePasswordReset'),
            $this->failIfCalled('deleteAllSessionsForUser')
        );

        $this->assertSame(204, $response['status']);
    }

    public function testResetPasswordMissingFieldsAreRejected(): void
    {
        $response = handleResetPasswordRequest(
            'POST',
            json_encode(['token' => 'abc']),
            $this->failIfCalled('findPasswordResetByTokenHash'),
            $this->failIfCalled('updateUserPassword'),
            $this->failIfCalled('deletePasswordReset'),
            $this->failIfCalled('deleteAllSessionsForUser')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testResetPasswordInvalidOrExpiredTokenIsRejected(): void
    {
        $response = handleResetPasswordRequest(
            'POST',
            json_encode(['token' => 'bad-token', 'newPassword' => 'newpassword1']),
            fn () => null,
            $this->failIfCalled('updateUserPassword'),
            $this->failIfCalled('deletePasswordReset'),
            $this->failIfCalled('deleteAllSessionsForUser')
        );

        $this->assertSame(400, $response['status']);
    }

    public function testResetPasswordWeakNewPasswordIsRejected(): void
    {
        $response = handleResetPasswordRequest(
            'POST',
            json_encode(['token' => 'good-token', 'newPassword' => 'short']),
            fn () => ['id' => 1, 'user_id' => 9],
            $this->failIfCalled('updateUserPassword'),
            $this->failIfCalled('deletePasswordReset'),
            $this->failIfCalled('deleteAllSessionsForUser')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testResetPasswordSuccessUpdatesPasswordConsumesTokenAndKillsSessions(): void
    {
        $updateArgs = null;
        $deletedResetId = null;
        $sessionsKilledForUserId = null;

        $response = handleResetPasswordRequest(
            'POST',
            json_encode(['token' => 'good-token', 'newPassword' => 'newpassword1']),
            fn () => ['id' => 1, 'user_id' => 9],
            function (...$args) use (&$updateArgs) {
                $updateArgs = $args;
            },
            function (int $resetId) use (&$deletedResetId) {
                $deletedResetId = $resetId;
            },
            function (int $userId) use (&$sessionsKilledForUserId) {
                $sessionsKilledForUserId = $userId;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame(9, $updateArgs[0]);
        $this->assertTrue(password_verify('newpassword1', $updateArgs[1]));
        $this->assertContains($updateArgs[2], ['weak', 'fair', 'good', 'strong']);
        $this->assertSame(1, $deletedResetId);
        $this->assertSame(9, $sessionsKilledForUserId);
    }
}
