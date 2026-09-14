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
        return function (array $answers, array $results, ?int $userId): void {};
    }

    public function testOptionsRequestReturnsNoContentWithoutRunningTheMatch(): void
    {
        $response = handleMatchRequest(
            'OPTIONS',
            '',
            null,
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
            null,
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
            null,
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
            null,
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
            null,
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

    public function testValidRequestLogsTheFullResultsAndUserId(): void
    {
        $fakeResults = [['name' => 'Lisbon', 'match' => 90], ['name' => 'Porto', 'match' => 70]];
        $loggedResults = null;
        $loggedUserId = 'not yet called';

        $response = handleMatchRequest(
            'POST',
            json_encode($this->validAnswers()),
            42,
            fn () => [],
            fn () => $fakeResults,
            function (array $answers, array $results, ?int $userId) use (&$loggedResults, &$loggedUserId): void {
                $loggedResults = $results;
                $loggedUserId = $userId;
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame($fakeResults, $loggedResults);
        $this->assertSame(42, $loggedUserId);
    }

    public function testValidRequestLogsNullUserIdForAGuest(): void
    {
        $loggedUserId = 'not yet called';

        handleMatchRequest(
            'POST',
            json_encode($this->validAnswers()),
            null,
            fn () => [],
            fn () => [],
            function (array $answers, array $results, ?int $userId) use (&$loggedUserId): void {
                $loggedUserId = $userId;
            }
        );

        $this->assertNull($loggedUserId);
    }
}
