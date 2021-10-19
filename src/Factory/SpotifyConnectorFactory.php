<?php

namespace App\Factory;

use App\Event\SpotifyTokenExpiredEvent;
use App\Objects\SpotifyCredentials;
use App\Service\SpotifyConnector;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyConnectorFactory
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        HttpClientInterface $httpClient,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->client = $httpClient->withOptions(['base_uri' => 'https://api.spotify.com']);
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Creates a spotify connector with the given credentials
     *
     * @param SpotifyCredentials $spotifyCredentials spotify credentials
     * @return SpotifyConnector
     */
    public function create(SpotifyCredentials $spotifyCredentials): SpotifyConnector
    {
        if ($spotifyCredentials->expired()) {
            $this->eventDispatcher->dispatch(new SpotifyTokenExpiredEvent($spotifyCredentials->accessToken()));
        }
        $connector = new SpotifyConnector($this->logger, $this->client, $spotifyCredentials);
        return $connector;
    }
}
