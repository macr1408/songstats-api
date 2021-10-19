<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SpotifyTokenExpiredEvent extends Event
{
    public const NAME = 'token.expired';

    private string $accessToken;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }
}
