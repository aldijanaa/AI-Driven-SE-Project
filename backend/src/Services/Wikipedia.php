<?php

/**
 * Resolves a destination to its Wikipedia article - photo, short extract,
 * and canonical URL - via Wikipedia's free, key-less REST summary API.
 * Used both to enrich the destination catalog (photo/link shown in the UI)
 * and, via `wikipedia_extract`, as an additional trusted-source grounding
 * chunk for the RAG description prompt in Rag.php.
 */

const WIKIPEDIA_SUMMARY_URL = 'https://en.wikipedia.org/api/rest_v1/page/summary/';

// A handful of destination names resolve to a Wikipedia article whose lead
// image/extract isn't representative of the place (a flag, a location-marker
// SVG, a municipality logo, a disambiguation page). For those, point at a
// better-illustrated article for the same place instead of the city's own
// title.
const WIKIPEDIA_TITLE_OVERRIDES = [
    'Santorini' => 'Fira',
    'Bali' => 'Ubud',
    'Amsterdam' => 'Dam Square',
    'Zanzibar' => 'Stone Town',
];

function fetchWikipediaSummary(string $title): ?array
{
    $url = WIKIPEDIA_SUMMARY_URL . rawurlencode(str_replace(' ', '_', $title));

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CAINFO => __DIR__ . '/../cacert.pem',
        CURLOPT_HTTPHEADER => [
            'User-Agent: TravelMatch-StudentProject/1.0 (https://github.com/; educational use)',
        ],
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $status !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || ($data['type'] ?? '') === 'disambiguation') {
        return null;
    }

    $photoUrl = $data['thumbnail']['source'] ?? null;
    $extract = $data['extract'] ?? null;
    $wikipediaUrl = $data['content_urls']['desktop']['page'] ?? null;

    if (!$photoUrl && !$extract) {
        return null;
    }

    return ['photo_url' => $photoUrl, 'wikipedia_extract' => $extract, 'wikipedia_url' => $wikipediaUrl];
}

function resolveDestinationWikipedia(string $name, string $country): array
{
    $candidateTitles = array_filter([
        WIKIPEDIA_TITLE_OVERRIDES[$name] ?? null,
        "{$name}, {$country}",
        $name,
    ]);

    foreach ($candidateTitles as $title) {
        $result = fetchWikipediaSummary($title);
        if ($result) {
            return $result;
        }
    }

    return ['photo_url' => null, 'wikipedia_extract' => null, 'wikipedia_url' => null];
}
