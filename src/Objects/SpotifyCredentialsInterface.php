<?php

namespace App\Objects;

interface SpotifyCredentialsInterface
{
    public function accessToken(): string;
    public function expiresIn(): \DateTime;
    public function refreshToken(): string;
    public function expired(): bool;
}
