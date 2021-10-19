<?php

namespace App\Event;

use App\Objects\SpotifyCredentialsInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UserLoggedInEvent extends Event
{
    public const NAME = 'user.loggedin';

    private SpotifyCredentialsInterface $spotifyCredentials;

    public function __construct(SpotifyCredentialsInterface $spotifyCredentials)
    {
        $this->spotifyCredentials = $spotifyCredentials;
    }

    public function getCredentials(): SpotifyCredentialsInterface
    {
        return $this->spotifyCredentials;
    }
}
