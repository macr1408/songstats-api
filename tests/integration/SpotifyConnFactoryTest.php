<?php

namespace Tests\Integration;

use App\Entity\UserToken;
use App\Objects\SpotifyCredentials;
use DateTime;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

class SpotifyConnFactoryTest extends TestCase
{

    private Generator $faker;

    public function setUp(): void
    {
        $this->faker = \Faker\Factory::create();
    }

    public function testItCreatesAConn()
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
    }
}
