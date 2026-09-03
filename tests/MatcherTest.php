<?php

use PHPUnit\Framework\TestCase;

/*
** Unit Tests for scoring/ranking logic behind destination reccomendation. 
1. testWeatherIdealTempMapsEachPreference — checks weatherIdealTemp() returns the correct ideal temperature for cold/mild/warm/hot, and a default for unknown input.
2. testScoreInterests — checks scoreInterests() averages the destination's scores for the selected interests, and returns a default (5.0) when no interests are given.
3. testScoreStyle — checks scoreStyle() returns relaxation_score for "relaxed", adventure_score for "adventurous", and the average of both for "balanced".
4. testScoreWeather — checks scoreWeather() gives a perfect score when the destination's temperature matches the preferred weather, and a lower/zero score as the difference grows.
5. testScoreBudget — checks scoreBudget() scores destinations higher when their budget level matches the user's, partially for adjacent tiers, and factors in how the requested amount compares to the destination's average cost.
6. testScoreGetaway — checks scoreGetaway() scores destinations by how many of a getaway type's vibe tags they share, with a default score when no getaway type is given.
7. testScoreCompanions — checks scoreCompanions() picks the right score based on travel companions (partner, family, friends, solo), including the romance-tag bonus for partners.
8. testFormatBestTime — checks formatBestTime() foil–June", or a single month like "July" when startand end are the same.
9. testBuildDescriptionMentionsTopInterests — checks buildDescription() includes the user's chosen interests in the generated text.
10. testRankDestinationsPutsTheBestMatchFirst — checks rankDestinations() ranks a strongly matching destination above a weakly matching one.
11. testRankDestinationsRespectsLimit — checks rankDestinations() returns no more results than the given limit.
12. testRankDestinationsMatchIsWithinZeroToHundredalways produces a match percentage between 0 and100.
*/

final class MatcherTest extends TestCase
{
    private function destination(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lisbon',
            'country' => 'Portugal',
            'budget_level' => 'moderate',
            'budget_min' => 800,
            'budget_max' => 1400,
            'best_month_start' => 4,
            'best_month_end' => 6,
            'temperature' => 22,
            'stay_min' => 4,
            'stay_max' => 7,
            'beach_score' => 7,
            'nature_score' => 5,
            'history_score' => 8,
            'food_score' => 9,
            'nightlife_score' => 7,
            'adventure_score' => 4,
            'relaxation_score' => 6,
            'family_score' => 6,
            'vibe_tags' => ['food', 'budget_friendly'],
        ], $overrides);
    }

    public function testWeatherIdealTempMapsEachPreference(): void
    {
        $this->assertSame(8.0, weatherIdealTemp('cold'));
        $this->assertSame(18.0, weatherIdealTemp('mild'));
        $this->assertSame(24.0, weatherIdealTemp('warm'));
        $this->assertSame(30.0, weatherIdealTemp('hot'));
        $this->assertSame(20.0, weatherIdealTemp('unknown'));
    }

    public function testScoreInterests(): void
    {
        $dest = $this->destination();

        $this->assertEqualsWithDelta(9.0, scoreInterests($dest, ['food']), 0.001);
        $this->assertEqualsWithDelta(8.5, scoreInterests($dest, ['food', 'history']), 0.001);
        $this->assertSame(5.0, scoreInterests($dest, []));
    }

    public function testScoreStyle(): void
    {
        $dest = $this->destination(['relaxation_score' => 3, 'adventure_score' => 9]);

        $this->assertSame(3.0, scoreStyle($dest, 'relaxed'));
        $this->assertSame(9.0, scoreStyle($dest, 'adventurous'));
        $this->assertEqualsWithDelta(6.0, scoreStyle($dest, 'balanced'), 0.001);
    }

    public function testScoreWeather(): void
    {
        $dest = $this->destination(['temperature' => 24]);

        $this->assertSame(10.0, scoreWeather($dest, 'warm'));
        $this->assertEqualsWithDelta(3.6, scoreWeather($dest, 'cold'), 0.001);
        $this->assertSame(0.0, scoreWeather($this->destination(['temperature' => 40]), 'cold'));
    }

    public function testScoreBudget(): void
    {
        $matched = $this->destination(['budget_level' => 'moderate', 'budget_min' => 900, 'budget_max' => 1100]);
        $mismatched = $this->destination(['budget_level' => 'luxury', 'budget_min' => 900, 'budget_max' => 1100]);

        $this->assertEqualsWithDelta(10.0, scoreBudget($matched, 'moderate', 1000), 0.001);
        $this->assertEqualsWithDelta(5.2, scoreBudget($mismatched, 'budget', 1000), 0.001);
    }

    public function testScoreGetaway(): void
    {
        $this->assertSame(5.0, scoreGetaway($this->destination(), null));
        $this->assertSame(5.0, scoreGetaway($this->destination(), 'not_a_real_option'));

        $partialOverlap = $this->destination(['vibe_tags' => ['food']]);
        $fullOverlap = $this->destination(['vibe_tags' => ['food', 'nightlife']]);

        $this->assertEqualsWithDelta(5.0, scoreGetaway($partialOverlap, 'food_nightlife'), 0.001);
        $this->assertEqualsWithDelta(10.0, scoreGetaway($fullOverlap, 'food_nightlife'), 0.001);
    }

    public function testScoreCompanions(): void
    {
        $romantic = $this->destination(['vibe_tags' => ['romance']]);
        $notRomantic = $this->destination(['vibe_tags' => ['food']]);

        $this->assertSame(10.0, scoreCompanions($romantic, 'partner'));
        $this->assertSame(6.0, scoreCompanions($notRomantic, 'partner'));
        $this->assertSame(6.0, scoreCompanions($this->destination(), 'solo'));
    }

    public function testFormatBestTime(): void
    {
        $this->assertSame('April–June', formatBestTime($this->destination(['best_month_start' => 4, 'best_month_end' => 6])));
        $this->assertSame('July', formatBestTime($this->destination(['best_month_start' => 7, 'best_month_end' => 7])));
    }

    public function testBuildDescriptionMentionsTopInterests(): void
    {
        $description = buildDescription($this->destination(), ['food', 'history']);

        $this->assertStringContainsString('food', $description);
        $this->assertStringContainsString('history', $description);
    }

    // rankDestinations() is the actual matching algorithm: it combines every
    // score above into one weighted match %, then ranks and limits results.

    public function testRankDestinationsPutsTheBestMatchFirst(): void
    {
        $strongMatch = $this->destination([
            'name' => 'Lisbon',
            'food_score' => 10,
            'relaxation_score' => 10,
            'temperature' => 24,
        ]);
        $weakMatch = $this->destination([
            'name' => 'Reykjavik',
            'food_score' => 1,
            'relaxation_score' => 1,
            'temperature' => 2,
        ]);

        $answers = ['interests' => ['food'], 'style' => 'relaxed', 'weather' => 'warm'];

        $ranked = rankDestinations($answers, [$weakMatch, $strongMatch]);

        $this->assertSame('Lisbon', $ranked[0]['name']);
        $this->assertGreaterThan($ranked[1]['match'], $ranked[0]['match']);
    }

    public function testRankDestinationsRespectsLimit(): void
    {
        $destinations = array_map(
            fn ($i) => $this->destination(['name' => "Dest{$i}"]),
            range(1, 5)
        );

        $ranked = rankDestinations(['interests' => []], $destinations, 2);

        $this->assertCount(2, $ranked);
    }

    public function testRankDestinationsMatchIsWithinZeroToHundred(): void
    {
        $answers = ['interests' => ['food'], 'style' => 'relaxed', 'weather' => 'warm', 'budgetLevel' => 'moderate', 'budgetAmount' => 1000];

        $ranked = rankDestinations($answers, [$this->destination()]);

        $this->assertGreaterThanOrEqual(0, $ranked[0]['match']);
        $this->assertLessThanOrEqual(100, $ranked[0]['match']);
    }
}
