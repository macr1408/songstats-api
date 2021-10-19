<?php

namespace App\Objects;

use App\Entity\UserToken;
use DateTime;

class SpotifyCredentials implements SpotifyCredentialsInterface
{
    private string $accessToken;
    private DateTime $expiresIn;
    private string $refreshToken;

    public function __construct(string $accessToken, DateTime $expiresIn, string $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->expiresIn = $expiresIn;
        $this->refreshToken = $refreshToken;
    }

    public static function fromUserToken(UserToken $userToken): SpotifyCredentials
    {
        return new SpotifyCredentials(
            $userToken->getAccessToken(),
            $userToken->getExpiresIn(),
            $userToken->getRefreshToken()
        );
    }

    public static function fromApiResponse(
        string $accessToken,
        string $expiresIn,
        string $refreshToken
    ): SpotifyCredentials {

        if (!$accessToken || !$expiresIn || !$refreshToken) {
            throw new \InvalidArgumentException('Tried to create spotify credentials with empty values');
        }

        $newExpiresIn = new \DateTime();
        $newExpiresIn->modify('+' . $expiresIn . ' seconds');
        return new SpotifyCredentials(
            $accessToken,
            $newExpiresIn,
            $refreshToken
        );
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function expiresIn(): DateTime
    {
        return $this->expiresIn;
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function expired(): bool
    {
        $now = new DateTime();
        if ($now < $this->expiresIn) {
            return false;
        }
        return true;
    }
}
