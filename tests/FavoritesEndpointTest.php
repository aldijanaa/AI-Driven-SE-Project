<?php

use PHPUnit\Framework\TestCase;

final class FavoritesEndpointTest extends TestCase
{
    private function authedUser(): callable
    {
        return fn () => ['id' => 9, 'first_name' => 'Ada'];
    }

    private function failIfCalled(string $name): callable
    {
        return fn (...$args) => $this->fail("{$name} should not be called");
    }

    public function testOptionsRequestReturnsNoContentWithoutTouchingTheDatabase(): void
    {
        $response = handleFavoritesRequest(
            'OPTIONS',
            '',
            null,
            $this->failIfCalled('findUserBySessionTokenHash'),
            $this->failIfCalled('findFavoriteDestinationsByUserId'),
            $this->failIfCalled('isFavorited'),
            $this->failIfCalled('addFavorite'),
            $this->failIfCalled('removeFavorite')
        );

        $this->assertSame(204, $response['status']);
    }

    public function testUnsupportedMethodIsRejected(): void
    {
        $response = handleFavoritesRequest(
            'DELETE',
            '',
            null,
            fn () => null,
            fn () => [],
            fn () => false,
            fn () => null,
            fn () => null
        );

        $this->assertSame(405, $response['status']);
    }

    public function testMissingAuthHeaderIsRejected(): void
    {
        $response = handleFavoritesRequest(
            'GET',
            '',
            null,
            $this->failIfCalled('findUserBySessionTokenHash'),
            $this->failIfCalled('findFavoriteDestinationsByUserId'),
            fn () => false,
            fn () => null,
            fn () => null
        );

        $this->assertSame(401, $response['status']);
    }

    public function testGetReturnsFavoriteDestinationsForTheAuthenticatedUser(): void
    {
        $fakeDestinations = [['id' => 1, 'name' => 'Lisbon']];
        $lookedUpUserId = null;

        $response = handleFavoritesRequest(
            'GET',
            '',
            'Bearer good-token',
            $this->authedUser(),
            function (int $userId) use ($fakeDestinations, &$lookedUpUserId) {
                $lookedUpUserId = $userId;
                return $fakeDestinations;
            },
            $this->failIfCalled('isFavorited'),
            $this->failIfCalled('addFavorite'),
            $this->failIfCalled('removeFavorite')
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame($fakeDestinations, $response['body']['destinations']);
        $this->assertSame(9, $lookedUpUserId);
    }

    public function testPostWithoutDestinationIdIsRejected(): void
    {
        $response = handleFavoritesRequest(
            'POST',
            json_encode([]),
            'Bearer good-token',
            $this->authedUser(),
            $this->failIfCalled('findFavoriteDestinationsByUserId'),
            $this->failIfCalled('isFavorited'),
            $this->failIfCalled('addFavorite'),
            $this->failIfCalled('removeFavorite')
        );

        $this->assertSame(422, $response['status']);
    }

    public function testPostAddsFavoriteWhenNotAlreadyFavorited(): void
    {
        $addedArgs = null;

        $response = handleFavoritesRequest(
            'POST',
            json_encode(['destinationId' => 4]),
            'Bearer good-token',
            $this->authedUser(),
            $this->failIfCalled('findFavoriteDestinationsByUserId'),
            fn (int $userId, int $destinationId) => false,
            function (int $userId, int $destinationId) use (&$addedArgs) {
                $addedArgs = [$userId, $destinationId];
            },
            $this->failIfCalled('removeFavorite')
        );

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['body']['favorited']);
        $this->assertSame([9, 4], $addedArgs);
    }

    public function testPostRemovesFavoriteWhenAlreadyFavorited(): void
    {
        $removedArgs = null;

        $response = handleFavoritesRequest(
            'POST',
            json_encode(['destinationId' => 4]),
            'Bearer good-token',
            $this->authedUser(),
            $this->failIfCalled('findFavoriteDestinationsByUserId'),
            fn (int $userId, int $destinationId) => true,
            $this->failIfCalled('addFavorite'),
            function (int $userId, int $destinationId) use (&$removedArgs) {
                $removedArgs = [$userId, $destinationId];
            }
        );

        $this->assertSame(200, $response['status']);
        $this->assertFalse($response['body']['favorited']);
        $this->assertSame([9, 4], $removedArgs);
    }
}
