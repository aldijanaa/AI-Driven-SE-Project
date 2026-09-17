<?php

use PHPUnit\Framework\TestCase;

final class ProfileEndpointTest extends TestCase
{
    private function authedUser(array $overrides = []): callable
    {
        $user = array_merge([
            'id' => 9,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password_hash' => password_hash('current-pass1', PASSWORD_BCRYPT),
        ], $overrides);

        return fn () => $user;
    }

    private function failIfCalled(string $name): callable
    {
        return fn (...$args) => $this->fail("{$name} should not be called");
    }

    private function validProfilePayload(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
        ], $overrides);
    }

    // --- handleUpdateProfileRequest ---

    public function testUpdateProfileOptionsReturnsNoContent(): void
    {
        $response = handleUpdateProfileRequest(
            'OPTIONS',
            '',
            null,
            $this->failIfCalled('findUserBySessionTokenHash'),
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('updateUserProfile')
        );

        $this->assertSame(204, $response['status']);
    }

    public function testUpdateProfileNonPostIsRejected(): void
    {
        $response = handleUpdateProfileRequest('GET', '', null, fn () => null, fn () => null, fn () => []);

        $this->assertSame(405, $response['status']);
    }

    public function testUpdateProfileRequiresAuth(): void
    {
        $response = handleUpdateProfileRequest(
            'POST',
            json_encode($this->validProfilePayload()),
            null,
            $this->failIfCalled('findUserBySessionTokenHash'),
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('updateUserProfile')
        );

        $this->assertSame(401, $response['status']);
    }

    public function testUpdateProfileMissingFieldIsRejected(): void
    {
        $payload = $this->validProfilePayload();
        unset($payload['lastName']);

        $response = handleUpdateProfileRequest(
            'POST',
            json_encode($payload),
            'Bearer good-token',
            $this->authedUser(),
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('updateUserProfile')
        );

        $this->assertSame(422, $response['status']);
        $this->assertSame('Missing required field: lastName', $response['body']['error']);
    }

    public function testUpdateProfileInvalidEmailIsRejected(): void
    {
        $response = handleUpdateProfileRequest(
            'POST',
            json_encode($this->validProfilePayload(['email' => 'not-an-email'])),
            'Bearer good-token',
            $this->authedUser(),
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('updateUserProfile')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testUpdateProfileEmailTakenByAnotherUserIsRejected(): void
    {
        $response = handleUpdateProfileRequest(
            'POST',
            json_encode($this->validProfilePayload(['email' => 'taken@example.com'])),
            'Bearer good-token',
            $this->authedUser(),
            fn (string $email) => ['id' => 999, 'email' => $email],
            $this->failIfCalled('updateUserProfile')
        );

        $this->assertSame(409, $response['status']);
    }

    public function testUpdateProfileAllowsKeepingOwnEmail(): void
    {
        $updateArgs = null;

        $response = handleUpdateProfileRequest(
            'POST',
            json_encode($this->validProfilePayload()),
            'Bearer good-token',
            $this->authedUser(),
            fn (string $email) => ['id' => 9, 'email' => $email],
            function (...$args) use (&$updateArgs) {
                $updateArgs = $args;
                return ['id' => 9, 'first_name' => $args[1], 'last_name' => $args[2], 'email' => $args[3]];
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame('Ada', $response['body']['user']['firstName']);
        $this->assertSame([9, 'Ada', 'Lovelace', 'ada@example.com'], $updateArgs);
    }

    // --- handleChangePasswordRequest ---

    public function testChangePasswordOptionsReturnsNoContent(): void
    {
        $response = handleChangePasswordRequest(
            'OPTIONS',
            '',
            null,
            $this->failIfCalled('findUserBySessionTokenHash'),
            $this->failIfCalled('updateUserPassword')
        );

        $this->assertSame(204, $response['status']);
    }

    public function testChangePasswordRequiresAuth(): void
    {
        $response = handleChangePasswordRequest(
            'POST',
            json_encode(['currentPassword' => 'x', 'newPassword' => 'y']),
            null,
            $this->failIfCalled('findUserBySessionTokenHash'),
            $this->failIfCalled('updateUserPassword')
        );

        $this->assertSame(401, $response['status']);
    }

    public function testChangePasswordWrongCurrentPasswordIsRejected(): void
    {
        $response = handleChangePasswordRequest(
            'POST',
            json_encode(['currentPassword' => 'wrong-pass1', 'newPassword' => 'new-password1']),
            'Bearer good-token',
            $this->authedUser(),
            $this->failIfCalled('updateUserPassword')
        );

        $this->assertSame(401, $response['status']);
        $this->assertSame('Current password is incorrect', $response['body']['error']);
    }

    public function testChangePasswordWeakNewPasswordIsRejected(): void
    {
        $response = handleChangePasswordRequest(
            'POST',
            json_encode(['currentPassword' => 'current-pass1', 'newPassword' => 'short']),
            'Bearer good-token',
            $this->authedUser(),
            $this->failIfCalled('updateUserPassword')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testChangePasswordSuccessUpdatesHashAndStrength(): void
    {
        $updateArgs = null;

        $response = handleChangePasswordRequest(
            'POST',
            json_encode(['currentPassword' => 'current-pass1', 'newPassword' => 'new-password1']),
            'Bearer good-token',
            $this->authedUser(),
            function (...$args) use (&$updateArgs) {
                $updateArgs = $args;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame(9, $updateArgs[0]);
        $this->assertTrue(password_verify('new-password1', $updateArgs[1]));
        $this->assertContains($updateArgs[2], ['weak', 'fair', 'good', 'strong']);
    }
}
