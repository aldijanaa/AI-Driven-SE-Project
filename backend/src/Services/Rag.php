<?php

require_once __DIR__ . '/../../database/Database.php';

/**
 * Retrieval step: pick the knowledge chunks most relevant to this
 * destination + this user's interests, ranked by how many of their tags
 * overlap with what the user cares about. `destination_knowledge` also
 * carries a `search_vector` tsvector column (see database/schema.sql) for
 * future free-text queries, but tag overlap is what's actually driving
 * ranking here since the tags are curated categories, not loose keywords.
 */
function retrieveKnowledge(string $destinationName, array $interests, string $budgetLevel, int $limit = 4): array
{
    $wantedTags = array_values(array_unique(array_filter(
        array_merge($interests, [$budgetLevel === 'budget' ? 'budget' : ''])
    )));

    if (!$wantedTags) {
        $wantedTags = [''];
    }

    $stmt = getDb()->prepare('
        SELECT k.body,
               cardinality(ARRAY(SELECT unnest(k.tags) INTERSECT SELECT unnest(:tags::text[]))) AS overlap
        FROM destination_knowledge k
        JOIN destinations d ON d.id = k.destination_id
        WHERE d.name = :name
        ORDER BY overlap DESC, k.id
        LIMIT :limit
    ');

    $stmt->bindValue(':name', $destinationName);
    $stmt->bindValue(':tags', '{' . implode(',', array_map(
        fn ($t) => '"' . str_replace('"', '\\"', $t) . '"',
        $wantedTags
    )) . '}');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return array_column($stmt->fetchAll(), 'body');
}

function buildRagPrompt(array $dest, array $answers, array $chunks): string
{
    $interests = implode(', ', $answers['interests'] ?? []);

    // Combine the curated, tag-matched knowledge chunks with the
    // destination's Wikipedia extract (if resolved) so the model is
    // grounded in an actual cited, trusted source rather than hand-written
    // facts alone - the same extract/link the UI shows the user.
    $allFacts = $chunks;
    if (!empty($dest['wikipedia_extract'])) {
        $allFacts[] = "(From Wikipedia) {$dest['wikipedia_extract']}";
    }
    $knowledgeBlock = $allFacts ? "Known facts about {$dest['name']}:\n- " . implode("\n- ", $allFacts) : '';

    return <<<PROMPT
        A traveler is looking for a destination recommendation. Write exactly
        2 short sentences (35 words max total) on why {$dest['name']}, {$dest['country']}
        fits them. Ground it in the facts given below rather than generic praise.
        Do not mention percentages or match scores. Do not use markdown.

        Traveler's stated interests: {$interests}
        Traveler's travel style: {$answers['style']}
        Traveler's budget level: {$answers['budgetLevel']}

        {$knowledgeBlock}

        Destination stats: best months {$dest['best_month_start']}-{$dest['best_month_end']},
        average temperature {$dest['temperature']}°C, budget level {$dest['budget_level']}.
        PROMPT;
}

function extractGeminiText(?string $response, int $status): ?string
{
    if (!$response || $status !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    $parts = $data['candidates'][0]['content']['parts'] ?? [];
    foreach ($parts as $part) {
        if (isset($part['text'])) {
            $text = trim(str_replace('*', '', $part['text']));
            // "Thinking" models occasionally leak scratchpad reasoning into
            // the visible answer instead of the requested 2-sentence blurb.
            // A clean answer is single-paragraph prose with no line breaks;
            // reject anything else rather than show it to a user.
            $looksClean = $text !== ''
                && str_word_count($text) <= 60
                && !str_contains($text, "\n");

            return $looksClean ? $text : null;
        }
    }

    return null;
}

const GROUNDING_OVERLAP_THRESHOLD = 0.25;

// Short function/filler words excluded from the overlap check so they don't
// inflate or deflate the score in either direction - only words that could
// plausibly carry a factual claim are compared.
const GROUNDING_STOPWORDS = [
    'the', 'and', 'for', 'with', 'this', 'that', 'from', 'your', 'you',
    'its', 'are', 'was', 'were', 'has', 'have', 'not', 'but', 'they',
    'their', 'offers', 'perfect', 'great', 'ideal', 'while', 'also',
    'into', 'onto', 'than', 'when', 'where', 'what', 'who', 'which',
];

/**
 * Lowercases, strips punctuation, and drops short/filler words - what's
 * left is the set of words a truthfulness check can meaningfully compare.
 */
function contentWords(string $text): array
{
    $normalized = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $text));
    $words = array_filter(
        explode(' ', $normalized),
        fn ($w) => strlen($w) > 3 && !in_array($w, GROUNDING_STOPWORDS, true)
    );

    return array_values($words);
}

/**
 * Truth/faithfulness gate: after Gemini writes a description, check that a
 * meaningful share of its content words actually come from the facts it was
 * given (the retrieved knowledge chunks, the Wikipedia extract, and the
 * destination's own name/country) rather than the model's own general
 * knowledge. This is a lexical heuristic, not semantic verification - it
 * tolerates normal paraphrasing but catches the case where the model
 * ignores the supplied grounding and answers from what it already "knows"
 * instead, which would share almost no vocabulary with the source facts.
 */
function isGrounded(string $generatedText, array $chunks, array $dest): bool
{
    $sourceText = implode(' ', $chunks)
        . ' ' . ($dest['wikipedia_extract'] ?? '')
        . ' ' . $dest['name'] . ' ' . $dest['country'];
    $sourceWords = array_flip(contentWords($sourceText));

    $generatedWords = contentWords($generatedText);
    if (!$generatedWords) {
        return false;
    }

    $matched = 0;
    foreach ($generatedWords as $word) {
        if (isset($sourceWords[$word])) {
            $matched++;
        }
    }

    return ($matched / count($generatedWords)) >= GROUNDING_OVERLAP_THRESHOLD;
}

/**
 * Generation step: ask Gemini to turn each destination's stats + retrieved
 * knowledge chunks + the user's own answers into a short, personalized
 * recommendation. Runs all requests concurrently via curl_multi since a
 * quiz submission needs one per top match and Gemini's "thinking" models
 * take several seconds each. Falls back to each entry's own template
 * description on any failure (missing API key, network error, timeout,
 * unclean response, or a response that fails the isGrounded() truthfulness
 * check) so the app stays functional without it.
 *
 * @param array $entries list of ['dest' => .., 'answers' => .., 'chunks' => .., 'fallback' => ..]
 * @return array[] list of ['text' => .., 'source' => 'gemini'|'fallback'], in the same order as
 *                  $entries - 'source' lets callers show the user whether a description is
 *                  LLM-grounded or the deterministic template, rather than presenting both as equal.
 */
function generateDescriptionsBatch(array $entries): array
{
    $apiKey = getenv('GEMINI_API_KEY');
    if (!$apiKey) {
        return array_map(fn ($e) => ['text' => $e['fallback'], 'source' => 'fallback'], $entries);
    }

    $model = getenv('GEMINI_MODEL') ?: 'gemini-flash-lite-latest';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

    $multi = curl_multi_init();
    $handles = [];

    foreach ($entries as $i => $entry) {
        $payload = json_encode([
            'contents' => [['parts' => [['text' => buildRagPrompt($entry['dest'], $entry['answers'], $entry['chunks'])]]]],
            'generationConfig' => ['maxOutputTokens' => 300],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CAINFO => __DIR__ . '/../cacert.pem',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "x-goog-api-key: {$apiKey}",
            ],
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[$i] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($multi, $running);
        if ($running > 0) {
            curl_multi_select($multi);
        }
    } while ($running > 0);

    $results = [];
    foreach ($handles as $i => $ch) {
        $response = curl_multi_getcontent($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);

        $text = extractGeminiText($response, $status);
        if ($text !== null && !isGrounded($text, $entries[$i]['chunks'], $entries[$i]['dest'])) {
            $text = null;
        }
        $results[$i] = $text !== null
            ? ['text' => $text, 'source' => 'gemini']
            : ['text' => $entries[$i]['fallback'], 'source' => 'fallback'];
    }
    curl_multi_close($multi);

    ksort($results);

    return array_values($results);
}
