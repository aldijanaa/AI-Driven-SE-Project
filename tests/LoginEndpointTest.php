<?php

use PHPUnit\Framework\TestCase;

final class LoginEndpointTest extends TestCase
{
    private function fakeUser(string $password, array $overrides = []): array
    {
        return array_merge([
            'id' => 7,
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.com',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'email_verified' => true,
        ], $overrides);
    }

    public function testOptionsRequestReturnsNoContent(): void
    {
        $response = handleLoginRequest('OPTIONS', '', fn () => null, fn () => null);

        $this->assertSame(204, $response['status']);
    }

    public function testNonPostMethodIsRejected(): void
    {
        $response = handleLoginRequest('GET', '', fn () => null, fn () => null);

        $this->assertSame(405, $response['status']);
    }

    public function testMissingFieldsAreRejected(): void
    {
        $response = handleLoginRequest('POST', json_encode(['email' => 'a@b.com']), fn () => null, fn () => null);

        $this->assertSame(422, $response['status']);
    }

    public function testUnknownEmailReturnsGenericError(): void
    {
        $response = handleLoginRequest(
            'POST',
            json_encode(['email' => 'nobody@example.com', 'password' => 'whatever1']),
            fn () => null,
            fn () => $this->fail('createSession should not be called on failed login')
        );

        $this->assertSame(401, $response['status']);
        $this->assertSame('Invalid email or password', $response['body']['error']);
    }

    public function testWrongPasswordReturnsGenericError(): void
    {
        $user = $this->fakeUser('correct-password1');

        $response = handleLoginRequest(
            'POST',
            json_encode(['email' => $user['email'], 'password' => 'wrong-password1']),
            fn () => $user,
            fn () => $this->fail('createSession should not be called on failed login')
        );

        $this->assertSame(401, $response['status']);
    }

    public function testUnverifiedAccountIsRejectedWithoutASession(): void
    {
        $user = $this->fakeUser('correct-password1', ['email_verified' => false]);

        $response = handleLoginRequest(
            'POST',
            json_encode(['email' => $user['email'], 'password' => 'correct-password1']),
            fn () => $user,
            fn () => $this->fail('createSession should not be called for an unverified account')
        );

        $this->assertSame(403, $response['status']);
        $this->assertTrue($response['body']['needsVerification']);
        $this->assertSame('grace@example.com', $response['body']['email']);
    }

    public function testCorrectCredentialsReturnUserAndToken(): void
    {
        $user = $this->fakeUser('correct-password1');
        $sessionArgs = null;

        $response = handleLoginRequest(
            'POST',
            json_encode(['email' => $user['email'], 'password' => 'correct-password1']),
            fn () => $user,
            function (...$args) use (&$sessionArgs) {
                $sessionArgs = $args;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame('Grace', $response['body']['user']['firstName']);
        $this->assertNotEmpty($response['body']['token']);
        $this->assertSame(7, $sessionArgs[0]);
    }
}
