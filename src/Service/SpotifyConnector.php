<?php

namespace App\Service;

use App\Objects\SpotifyCredentials;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyConnector
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private SpotifyCredentials $spotifyCredentials;

    public function __construct(
        LoggerInterface $logger,
        HttpClientInterface $httpClient,
        SpotifyCredentials $spotifyCredentials
    ) {
        $this->client = $httpClient;
        $this->logger = $logger;
        $this->spotifyCredentials = $spotifyCredentials;
    }

    /**
     * Gets the info about the current user
     *
     * @return array current user info
     */
    public function getUser(): array
    {
        $response = $this->client->request('GET', '/v1/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->spotifyCredentials->accessToken()
            ]
        ]);
        if ($response->getStatusCode() > 299) {
            $this->logger->error($response->getContent(false));
            return [];
        }

        return $response->toArray();
    }

    /**
     * Gets the info about the current playing status
     *
     * @return array current playing info
     */
    public function getCurrentPlay(): array
    {
        $response = $this->client->request('GET', '/v1/me/player/currently-playing', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->spotifyCredentials->accessToken()
            ]
        ]);
        if ($response->getStatusCode() > 299 || $response->getContent() === '') {
            $this->logger->error($response->getContent(false));
            return [];
        }

        return $response->toArray();
    }

    /**
     * Gets the song stats given a song ID
     *
     * @param string $songId ID of the song
     * @return array song stats
     */
    public function getSongStats(string $songId): array
    {
        $response = $this->client->request('GET', '/v1/tracks/' . $songId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->spotifyCredentials->accessToken()
            ]
        ]);
        if ($response->getStatusCode() > 299 || $response->getContent() === '') {
            $this->logger->error($response->getContent(false));
            return [];
        }

        return $response->toArray();
    }
}
