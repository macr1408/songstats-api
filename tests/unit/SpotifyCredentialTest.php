<?php

namespace Tests\Unit;

use App\Entity\UserToken;
use App\Objects\SpotifyCredentials;
use DateTime;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

class SpotifyCredentialTest extends TestCase
{

    private Generator $faker;

    public function setUp(): void
    {
        $this->faker = \Faker\Factory::create();
    }

    public function testItCreatesFromApiResponse()
    {
        $apiResponse = [
            'access_token' => $this->faker->md5,
            'expires_in' => 3600,
            'refresh_token' => $this->faker->md5
        ];

        $credentials = SpotifyCredentials::fromApiResponse(
            $apiResponse['access_token'],
            $apiResponse['expires_in'],
            $apiResponse['refresh_token']
        );

        $now = new DateTime();
        $now->modify('+3600 seconds');
        $this->assertEquals($apiResponse['access_token'], $credentials->accessToken());
        $this->assertEquals($now->format('h'), $credentials->expiresIn()->format('h'));
        $this->assertEquals($apiResponse['refresh_token'], $credentials->refreshToken());
        $this->assertNotTrue($credentials->expired());
    }

    public function testItCreatesFromUserToken()
    {
        $userToken = new UserToken();
        $userToken->setAccessToken($this->faker->md5);
        $now = new DateTime();
        $userToken->setExpiresIn($now->modify('+3600 seconds'));
        $userToken->setRefreshToken($this->faker->md5);

        $credentials = SpotifyCredentials::fromUserToken($userToken);

        $now = new DateTime();
        $now->modify('+3600 seconds');
        $this->assertEquals($userToken->getAccessToken(), $credentials->accessToken());
        $this->assertEquals($now->format('h'), $credentials->expiresIn()->format('h'));
        $this->assertEquals($userToken->getRefreshToken(), $credentials->refreshToken());
        $this->assertNotTrue($credentials->expired());
    }
}
