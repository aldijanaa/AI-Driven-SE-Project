<?php

use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function testShortPasswordIsRejected(): void
    {
        $this->assertFalse(isPasswordAcceptable('ab1'));
    }

    public function testPasswordWithoutANumberIsRejected(): void
    {
        $this->assertFalse(isPasswordAcceptable('longenoughpassword'));
    }

    public function testPasswordWithoutALetterIsRejected(): void
    {
        $this->assertFalse(isPasswordAcceptable('12345678'));
    }

    public function testValidPasswordIsAccepted(): void
    {
        $this->assertTrue(isPasswordAcceptable('password1'));
    }

    public function testShortPasswordScoresWeak(): void
    {
        $this->assertSame('weak', passwordStrength('abc1')['label']);
    }

    public function testLongVariedPasswordScoresStrong(): void
    {
        $this->assertSame('strong', passwordStrength('Tr@velMatch2026!')['label']);
    }
}
