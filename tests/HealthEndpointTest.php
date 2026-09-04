<?php

use PHPUnit\Framework\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function testHealthCheckReturnsOkStatus(): void
    {
        $this->assertSame(['status' => 'ok'], healthCheck());
    }
}
