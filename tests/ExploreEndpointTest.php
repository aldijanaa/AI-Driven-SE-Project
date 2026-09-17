<?php

use PHPUnit\Framework\TestCase;

final class ExploreEndpointTest extends TestCase
{
    public function testOptionsRequestReturnsNoContentWithoutTouchingTheDatabase(): void
    {
        $response = handleExploreRequest(
            'OPTIONS',
            fn () => throw new Exception('getDestinations should not be called for OPTIONS')
        );

        $this->assertSame(204, $response['status']);
        $this->assertNull($response['body']);
    }

    public function testNonGetMethodIsRejected(): void
    {
        $response = handleExploreRequest('POST', fn () => []);

        $this->assertSame(405, $response['status']);
    }

    public function testValidRequestReturnsDestinationsFromInjectedDependency(): void
    {
        $fakeDestinations = [
            ['name' => 'Lisbon', 'match_count' => 12],
            ['name' => 'Porto', 'match_count' => 3],
        ];

        $response = handleExploreRequest('GET', fn () => $fakeDestinations);

        $this->assertSame(200, $response['status']);
        $this->assertSame(['destinations' => $fakeDestinations], $response['body']);
    }
}
