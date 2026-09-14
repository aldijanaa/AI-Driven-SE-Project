<?php

use PHPUnit\Framework\TestCase;

final class RegisterEndpointTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password1',
        ], $overrides);
    }

    private function noSuchUser(): callable
    {
        return fn () => null;
    }

    private function failIfCalled(string $name): callable
    {
        return fn (...$args) => $this->fail("{$name} should not be called");
    }

    public function testOptionsRequestReturnsNoContentWithoutTouchingTheDatabase(): void
    {
        $response = handleRegisterRequest(
            'OPTIONS',
            '',
            $this->failIfCalled('findUserByEmail'),
            $this->failIfCalled('createUser'),
            $this->failIfCalled('replaceEmailVerificationForUser'),
            $this->failIfCalled('sendVerificationEmail')
        );

        $this->assertSame(204, $response['status']);
        $this->assertNull($response['body']);
    }

    public function testNonPostMethodIsRejected(): void
    {
        $response = handleRegisterRequest('GET', '', $this->noSuchUser(), fn () => [], fn () => null, fn () => null);

        $this->assertSame(405, $response['status']);
    }

    public function testInvalidJsonBodyIsRejected(): void
    {
        $response = handleRegisterRequest(
            'POST',
            '{not valid',
            $this->noSuchUser(),
            fn () => [],
            fn () => null,
            fn () => null
        );

        $this->assertSame(400, $response['status']);
    }

    public function testMissingFieldIsRejected(): void
    {
        $payload = $this->validPayload();
        unset($payload['lastName']);

        $response = handleRegisterRequest(
            'POST',
            json_encode($payload),
            $this->noSuchUser(),
            fn () => [],
            fn () => null,
            fn () => null
        );

        $this->assertSame(422, $response['status']);
        $this->assertSame('Missing required field: lastName', $response['body']['error']);
    }

    public function testInvalidEmailIsRejected(): void
    {
        $response = handleRegisterRequest(
            'POST',
            json_encode($this->validPayload(['email' => 'not-an-email'])),
            $this->noSuchUser(),
            fn () => [],
            fn () => null,
            fn () => null
        );

        $this->assertSame(422, $response['status']);
    }

    public function testWeakPasswordIsRejected(): void
    {
        $response = handleRegisterRequest(
            'POST',
            json_encode($this->validPayload(['password' => 'short'])),
            $this->noSuchUser(),
            fn () => [],
            fn () => null,
            fn () => null
        );

        $this->assertSame(422, $response['status']);
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $response = handleRegisterRequest(
            'POST',
            json_encode($this->validPayload()),
            fn (string $email) => ['id' => 1, 'email' => $email],
            $this->failIfCalled('createUser'),
            $this->failIfCalled('replaceEmailVerificationForUser'),
            $this->failIfCalled('sendVerificationEmail')
        );

        $this->assertSame(409, $response['status']);
    }

    public function testValidRequestCreatesUserAndSendsVerificationCodeWithoutASession(): void
    {
        $createdUserArgs = null;
        $verificationArgs = null;
        $sentTo = null;

        $response = handleRegisterRequest(
            'POST',
            json_encode($this->validPayload()),
            $this->noSuchUser(),
            function (...$args) use (&$createdUserArgs) {
                $createdUserArgs = $args;
                return [
                    'id' => 42,
                    'first_name' => $args[0],
                    'last_name' => $args[1],
                    'email' => $args[2],
                ];
            },
            function (...$args) use (&$verificationArgs) {
                $verificationArgs = $args;
            },
            function (...$args) use (&$sentTo) {
                $sentTo = $args;
            }
        );

        $this->assertSame(201, $response['status']);
        $this->assertSame('Ada', $response['body']['user']['firstName']);
        $this->assertSame('ada@example.com', $response['body']['user']['email']);
        $this->assertArrayNotHasKey('password', $response['body']['user']);
        $this->assertArrayNotHasKey('token', $response['body']);
        $this->assertTrue($response['body']['needsVerification']);

        $this->assertSame('Ada', $createdUserArgs[0]);
        $this->assertSame('Lovelace', $createdUserArgs[1]);
        $this->assertSame('ada@example.com', $createdUserArgs[2]);
        $this->assertTrue(password_verify('password1', $createdUserArgs[3]));
        $this->assertContains($createdUserArgs[4], ['weak', 'fair', 'good', 'strong']);

        $this->assertSame(42, $verificationArgs[0]);
        $this->assertSame(64, strlen($verificationArgs[1]));

        $this->assertSame('ada@example.com', $sentTo[0]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sentTo[1]);
    }
}
