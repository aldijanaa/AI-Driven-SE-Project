<?php

use PHPUnit\Framework\TestCase;

final class EmbeddingsTest extends TestCase
{
    public function testCosineSimilarityIsOneForIdenticalVectors(): void
    {
        $this->assertEqualsWithDelta(1.0, cosineSimilarity([1, 2, 3], [1, 2, 3]), 0.0001);
    }

    public function testCosineSimilarityIsZeroForOrthogonalVectors(): void
    {
        $this->assertEqualsWithDelta(0.0, cosineSimilarity([1, 0], [0, 1]), 0.0001);
    }

    public function testCosineSimilarityIsNegativeOneForOppositeVectors(): void
    {
        $this->assertEqualsWithDelta(-1.0, cosineSimilarity([1, 0], [-1, 0]), 0.0001);
    }

    public function testCosineSimilarityIsZeroForMismatchedOrEmptyVectors(): void
    {
        $this->assertSame(0.0, cosineSimilarity([1, 2], [1, 2, 3]));
        $this->assertSame(0.0, cosineSimilarity([], []));
    }

    public function testBuildDestinationEmbeddingTextIncludesKeyFields(): void
    {
        $dest = [
            'name' => 'Lisbon',
            'country' => 'Portugal',
            'budget_level' => 'moderate',
            'vibe_tags' => ['food', 'budget_friendly'],
        ];

        $text = buildDestinationEmbeddingText($dest, ['Great pastries at every corner.']);

        $this->assertStringContainsString('Lisbon', $text);
        $this->assertStringContainsString('Portugal', $text);
        $this->assertStringContainsString('food', $text);
        $this->assertStringContainsString('budget friendly', $text);
        $this->assertStringContainsString('moderate', $text);
        $this->assertStringContainsString('Great pastries at every corner.', $text);
    }
}
