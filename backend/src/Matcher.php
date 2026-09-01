<?php

require_once __DIR__ . '/Rag.php';

const INTEREST_TO_SCORE_FIELD = [
    'beach' => 'beach_score',
    'nature' => 'nature_score',
    'history' => 'history_score',
    'food' => 'food_score',
    'nightlife' => 'nightlife_score',
    'adventure' => 'adventure_score',
    'relaxation' => 'relaxation_score',
    'family' => 'family_score',
];

const INTEREST_LABELS = [
    'beach' => 'beaches',
    'nature' => 'mountains and nature',
    'history' => 'history and museums',
    'food' => 'food',
    'nightlife' => 'nightlife',
    'adventure' => 'adventure',
    'relaxation' => 'relaxation',
    'family' => 'family-friendly activities',
];

const GETAWAY_VIBES = [
    'modest_history' => ['modest_history', 'budget_friendly'],
    'romantic_luxury' => ['romance', 'luxury'],
    'nature_adventure' => ['nature_escape', 'adventure', 'budget_friendly'],
    'modern_luxury' => ['luxury', 'modern'],
    'food_nightlife' => ['food', 'nightlife'],
];

const MONTH_NAMES = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
];

function weatherIdealTemp(string $preference): float
{
    return match ($preference) {
        'cold' => 8,
        'mild' => 18,
        'warm' => 24,
        'hot' => 30,
        default => 20,
    };
}

function scoreInterests(array $dest, array $interests): float
{
    if (empty($interests)) {
        return 5.0;
    }

    $total = 0;
    foreach ($interests as $interest) {
        $field = INTEREST_TO_SCORE_FIELD[$interest] ?? null;
        $total += $field ? $dest[$field] : 0;
    }

    return $total / count($interests);
}

function scoreStyle(array $dest, string $style): float
{
    return match ($style) {
        'relaxed' => $dest['relaxation_score'],
        'adventurous' => $dest['adventure_score'],
        default => ($dest['relaxation_score'] + $dest['adventure_score']) / 2,
    };
}

function scoreWeather(array $dest, string $weatherPreference): float
{
    $ideal = weatherIdealTemp($weatherPreference);
    $diff = abs($dest['temperature'] - $ideal);

    return max(0, 10 - ($diff / 2.5));
}

function scoreBudget(array $dest, string $budgetLevel, float $budgetAmount): float
{
    $levelScore = $dest['budget_level'] === $budgetLevel ? 10 : match (true) {
        ($budgetLevel === 'budget' && $dest['budget_level'] === 'moderate') ||
        ($budgetLevel === 'moderate' && $dest['budget_level'] === 'budget') ||
        ($budgetLevel === 'moderate' && $dest['budget_level'] === 'luxury') ||
        ($budgetLevel === 'luxury' && $dest['budget_level'] === 'moderate') => 6,
        default => 2,
    };

    $avgCost = ($dest['budget_min'] + $dest['budget_max']) / 2;
    $amountScore = $avgCost <= 0 ? 10 : max(0, min(10, ($budgetAmount / $avgCost) * 10));

    return ($levelScore * 0.6) + ($amountScore * 0.4);
}

function scoreGetaway(array $dest, ?string $getaway): float
{
    if (!$getaway || !isset(GETAWAY_VIBES[$getaway])) {
        return 5.0;
    }

    $tags = GETAWAY_VIBES[$getaway];
    $shared = count(array_intersect($tags, $dest['vibe_tags']));

    return ($shared / count($tags)) * 10;
}

function scoreCompanions(array $dest, string $companions): float
{
    return match ($companions) {
        'family' => $dest['family_score'],
        'partner' => in_array('romance', $dest['vibe_tags'], true) ? 10 : 6,
        'friends' => $dest['nightlife_score'],
        default => 6, // solo
    };
}

function buildDescription(array $dest, array $interests): string
{
    $candidates = !empty($interests) ? $interests : array_keys(INTEREST_TO_SCORE_FIELD);

    $scored = [];
    foreach ($candidates as $interest) {
        $field = INTEREST_TO_SCORE_FIELD[$interest] ?? null;
        if ($field) {
            $scored[$interest] = $dest[$field];
        }
    }
    arsort($scored);
    $topInterests = array_slice(array_keys($scored), 0, 3);
    $labels = array_map(fn ($i) => INTEREST_LABELS[$i], $topInterests);

    $interestPhrase = count($labels) > 1
        ? implode(', ', array_slice($labels, 0, -1)) . ' and ' . end($labels)
        : ($labels[0] ?? 'a bit of everything');

    $temp = $dest['temperature'];
    $weatherAdj = match (true) {
        $temp >= 28 => 'hot',
        $temp >= 22 => 'warm',
        $temp >= 15 => 'mild',
        default => 'cool',
    };

    $priceClause = match ($dest['budget_level']) {
        'budget' => 'and prices stay very affordable',
        'luxury' => 'although prices run higher, especially in peak season',
        default => 'with reasonably balanced prices',
    };

    $startMonth = MONTH_NAMES[$dest['best_month_start']];

    return "Great combination of {$interestPhrase}. {$startMonth} onward brings {$weatherAdj} weather, {$priceClause}.";
}

function formatBestTime(array $dest): string
{
    $start = MONTH_NAMES[$dest['best_month_start']];
    $end = MONTH_NAMES[$dest['best_month_end']];

    return $start === $end ? $start : "{$start}–{$end}";
}

function matchDestinations(array $answers, array $destinations, int $limit = 3): array
{
    $interests = array_slice($answers['interests'] ?? [], 0, 4);
    $style = $answers['style'] ?? 'balanced';
    $weather = $answers['weather'] ?? 'warm';
    $budgetLevel = $answers['budgetLevel'] ?? 'moderate';
    $budgetAmount = (float) ($answers['budgetAmount'] ?? 1000);
    $getaway = $answers['getaway'] ?? null;
    $companions = $answers['companions'] ?? 'solo';

    $weights = [
        'interests' => 0.35,
        'style' => 0.15,
        'weather' => 0.15,
        'budget' => 0.15,
        'getaway' => 0.15,
        'companions' => 0.05,
    ];

    $results = [];
    foreach ($destinations as $dest) {
        $scores = [
            'interests' => scoreInterests($dest, $interests),
            'style' => scoreStyle($dest, $style),
            'weather' => scoreWeather($dest, $weather),
            'budget' => scoreBudget($dest, $budgetLevel, $budgetAmount),
            'getaway' => scoreGetaway($dest, $getaway),
            'companions' => scoreCompanions($dest, $companions),
        ];

        $total = 0;
        foreach ($weights as $key => $weight) {
            $total += ($scores[$key] / 10) * $weight;
        }

        $results[] = [
            'name' => $dest['name'],
            'country' => $dest['country'],
            'match' => (int) round(max(0, min(100, $total * 100))),
            'description' => buildDescription($dest, $interests),
            'best_time' => formatBestTime($dest),
            'budget_estimate' => sprintf('€%d–%d', $dest['budget_min'], $dest['budget_max']),
            'recommended_stay' => "{$dest['stay_min']}–{$dest['stay_max']} days",
        ];
    }

    usort($results, fn ($a, $b) => $b['match'] <=> $a['match']);

    $topResults = array_slice($results, 0, $limit);
    $destinationsByName = array_column($destinations, null, 'name');

    $ragEntries = array_map(function ($result) use ($destinationsByName, $interests, $budgetLevel, $answers) {
        $dest = $destinationsByName[$result['name']];

        return [
            'dest' => $dest,
            'answers' => $answers,
            'chunks' => retrieveKnowledge($dest['name'], $interests, $budgetLevel),
            'fallback' => $result['description'],
        ];
    }, $topResults);

    $descriptions = generateDescriptionsBatch($ragEntries);

    foreach ($topResults as $i => &$result) {
        $result['description'] = $descriptions[$i];
    }
    unset($result);

    return $topResults;
}
