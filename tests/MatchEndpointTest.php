<?php

use PHPUnit\Framework\TestCase;

final class MatchEndpointTest extends TestCase
{
    private function validAnswers(array $overrides = []): array
    {
        return array_merge([
            'interests' => ['food'],
            'style' => 'relaxed',
            'weather' => 'warm',
            'budgetLevel' => 'moderate',
            'companions' => 'solo',
        ], $overrides);
    }

    private function noopLogSubmission(): callable
    {
        return function (array $answers, ?array $topMatch): void {};
    }

    public function testOptionsRequestReturnsNoContentWithoutRunningTheMatch(): void
    {
        $response = handleMatchRequest(
            'OPTIONS',
            '',
            fn () => $this->fail('getDestinations should not be called for OPTIONS'),
            fn () => $this->fail('matchDestinations should not be called for OPTIONS'),
            fn () => $this->fail('logSubmission should not be called for OPTIONS')
        );

        $this->assertSame(204, $response['status']);
        $this->assertNull($response['body']);
    }

    public function testNonPostMethodIsRejected(): void
    {
        $response = handleMatchRequest(
            'GET',
            '',
            fn () => [],
            fn () => [],
            $this->noopLogSubmission()
        );

        $this->assertSame(405, $response['status']);
        $this->assertSame('Method not allowed', $response['body']['error']);
    }

    public function testInvalidJsonBodyIsRejected(): void
    {
        $response = handleMatchRequest(
            'POST',
            '{not valid json',
            fn () => [],
            fn () => [],
            $this->noopLogSubmission()
        );

        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid JSON body', $response['body']['error']);
    }

    public function testMissingRequiredFieldIsRejected(): void
    {
        $answers = $this->validAnswers();
        unset($answers['weather']);

        $response = handleMatchRequest(
            'POST',
            json_encode($answers),
            fn () => [],
            fn () => [],
            $this->noopLogSubmission()
        );

        $this->assertSame(422, $response['status']);
        $this->assertSame('Missing required field: weather', $response['body']['error']);
    }

    public function testValidRequestReturnsMatchResultsFromInjectedDependencies(): void
    {
        $fakeDestinations = [['name' => 'Lisbon']];
        $fakeResults = [['name' => 'Lisbon', 'match' => 90]];

        $response = handleMatchRequest(
            'POST',
            json_encode($this->validAnswers()),
            fn () => $fakeDestinations,
            function (array $answers, array $destinations) use ($fakeDestinations, $fakeResults) {
                $this->assertSame($fakeDestinations, $destinations);
                return $fakeResults;
            },
            $this->noopLogSubmission()
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame(['results' => $fakeResults], $response['body']);
    }

    public function testValidRequestLogsTheTopMatch(): void
    {
        $fakeResults = [['name' => 'Lisbon', 'match' => 90], ['name' => 'Porto', 'match' => 70]];
        $logged = null;

        $response = handleMatchRequest(
            'POST',
            json_encode($this->validAnswers()),
            fn () => [],
            fn () => $fakeResults,
            function (array $answers, ?array $topMatch) use (&$logged): void {
                $logged = $topMatch;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame($fakeResults[0], $logged);
    }

    public function testValidRequestLogsNullTopMatchWhenThereAreNoResults(): void
    {
        $logged = 'not yet called';

        handleMatchRequest(
            'POST',
            json_encode($this->validAnswers()),
            fn () => [],
            fn () => [],
            function (array $answers, ?array $topMatch) use (&$logged): void {
                $logged = $topMatch;
            }
        );

        $this->assertNull($logged);
    }
}
