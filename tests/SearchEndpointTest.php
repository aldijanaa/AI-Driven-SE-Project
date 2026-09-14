<?php

use PHPUnit\Framework\TestCase;

final class SearchEndpointTest extends TestCase
{
    public function testOptionsRequestReturnsNoContentWithoutEmbedding(): void
    {
        $response = handleSearchRequest(
            'OPTIONS',
            null,
            fn () => throw new Exception('embedQuery should not be called for OPTIONS'),
            fn () => throw new Exception('getSearchableDestinations should not be called for OPTIONS')
        );

        $this->assertSame(204, $response['status']);
        $this->assertNull($response['body']);
    }

    public function testNonGetMethodIsRejected(): void
    {
        $response = handleSearchRequest('POST', 'beach', fn () => [], fn () => []);

        $this->assertSame(405, $response['status']);
    }

    public function testMissingQueryIsRejected(): void
    {
        $response = handleSearchRequest('GET', null, fn () => [], fn () => []);

        $this->assertSame(422, $response['status']);
    }

    public function testBlankQueryIsRejected(): void
    {
        $response = handleSearchRequest('GET', '   ', fn () => [], fn () => []);

        $this->assertSame(422, $response['status']);
    }

    public function testEmbeddingFailureReturnsServiceUnavailable(): void
    {
        $response = handleSearchRequest('GET', 'beach town', fn () => null, fn () => []);

        $this->assertSame(503, $response['status']);
    }

    public function testResultsAreRankedBySimilarityWithEmbeddingStripped(): void
    {
        $fakeDestinations = [
            ['name' => 'Lisbon', 'embedding' => [1, 0]],
            ['name' => 'Zanzibar', 'embedding' => [0, 1]],
            ['name' => 'NoEmbeddingYet', 'embedding' => null],
        ];

        $response = handleSearchRequest(
            'GET',
            'sunny beach',
            fn () => [1, 0],
            fn () => $fakeDestinations
        );

        $this->assertSame(200, $response['status']);

        $names = array_column($response['body']['destinations'], 'name');
        $this->assertSame(['Lisbon', 'Zanzibar'], $names);
        $this->assertArrayNotHasKey('embedding', $response['body']['destinations'][0]);
        $this->assertArrayHasKey('similarity', $response['body']['destinations'][0]);
    }

    public function testResultsAreLimitedToEight(): void
    {
        $fakeDestinations = array_map(
            fn ($i) => ['name' => "Dest{$i}", 'embedding' => [1, 0]],
            range(1, 12)
        );

        $response = handleSearchRequest('GET', 'anywhere', fn () => [1, 0], fn () => $fakeDestinations);

        $this->assertCount(8, $response['body']['destinations']);
    }
}
