<?php

use PHPUnit\Framework\TestCase;

/*
** Unit tests for the RAG truthfulness gate in Rag.php. These exercise pure
** functions only (contentWords, isGrounded) - no network call, no
** database, matching the project's approach of unit-testing everything
** above the boundary of an external service.
1. testContentWordsLowercasesAndStripsPunctuation — checks contentWords() normalizes case and removes punctuation.
2. testContentWordsDropsShortAndStopWords — checks contentWords() excludes words of length <= 3 and words in the stopword list.
3. testIsGroundedTrueWhenTextReflectsSourceFacts — checks isGrounded() accepts a generated description that reuses vocabulary from the retrieved chunks.
4. testIsGroundedTrueWhenTextOnlyMatchesDestinationNameAndCountry — checks isGrounded() accepts text grounded only in the destination's own name/country (no chunks retrieved).
5. testIsGroundedFalseWhenTextIsUnrelatedToSource — checks isGrounded() rejects a description that shares no real vocabulary with the source facts.
6. testIsGroundedFalseWhenGeneratedTextIsEmpty — checks isGrounded() rejects an empty/all-stopword generated string.
7. testIsGroundedUsesWikipediaExtractAsSource — checks isGrounded() accepts text grounded in the Wikipedia extract even with no curated chunks.
*/

final class RagTest extends TestCase
{
    private function destination(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lisbon',
            'country' => 'Portugal',
            'wikipedia_extract' => null,
        ], $overrides);
    }

    public function testContentWordsLowercasesAndStripsPunctuation(): void
    {
        $this->assertSame(['lisbon', 'famous', 'custard', 'tarts'], contentWords('Lisbon, famous for custard tarts!'));
    }

    public function testContentWordsDropsShortAndStopWords(): void
    {
        // "of", "the", "for", "you" are filler; "a" and "at" are too short.
        $this->assertSame(['taste', 'pasteis', 'belem', 'bakery'], contentWords('a taste of the Pasteis at Belem bakery for you'));
    }

    public function testIsGroundedTrueWhenTextReflectsSourceFacts(): void
    {
        $chunks = ['The historic Alfama district is known for narrow streets and fado music.'];
        $text = 'Lisbon charms with the historic Alfama district, where fado music drifts through narrow streets.';

        $this->assertTrue(isGrounded($text, $chunks, $this->destination()));
    }

    public function testIsGroundedTrueWhenTextOnlyMatchesDestinationNameAndCountryContext(): void
    {
        $text = 'Lisbon, Portugal blends historic charm with a relaxed coastal atmosphere.';

        $this->assertTrue(isGrounded($text, [], $this->destination()));
    }

    public function testIsGroundedFalseWhenTextIsUnrelatedToSource(): void
    {
        $chunks = ['The historic Alfama district is known for narrow streets and fado music.'];
        $text = 'This exciting metropolis boasts thrilling nightlife, towering skyscrapers, and endless shopping malls.';

        $this->assertFalse(isGrounded($text, $chunks, $this->destination()));
    }

    public function testIsGroundedFalseWhenGeneratedTextIsEmpty(): void
    {
        $this->assertFalse(isGrounded('', ['Some retrieved fact about the destination.'], $this->destination()));
    }

    public function testIsGroundedUsesWikipediaExtractAsSource(): void
    {
        $dest = $this->destination([
            'wikipedia_extract' => 'Lisbon is the westernmost capital city in mainland Europe, built across seven hills.',
        ]);
        $text = 'Lisbon spreads across seven hills as the westernmost capital city in mainland Europe.';

        $this->assertTrue(isGrounded($text, [], $dest));
    }
}
