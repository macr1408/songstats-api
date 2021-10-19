<?php

namespace App\EventSubscriber;

use App\Event\SpotifyTokenExpiredEvent;
use App\Exception\JsonException;
use App\Objects\SpotifyAuthCredentials;
use App\Objects\SpotifyCredentials;
use App\Repository\UserTokenRepository;
use App\Service\SpotifyAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SpotifyTokenExpiredSubscriber implements EventSubscriberInterface
{

    private UserTokenRepository $userTokenRepository;
    private SpotifyAuthenticator $spotifyAuthenticator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserTokenRepository $userTokenRepository,
        SpotifyAuthenticator $spotifyAuthenticator,
        EntityManagerInterface $entityManager
    ) {
        $this->userTokenRepository = $userTokenRepository;
        $this->spotifyAuthenticator = $spotifyAuthenticator;
        $this->entityManager = $entityManager;
    }

    /**
     * Refreshes the current spotify token if expired
     *
     * @param SpotifyTokenExpiredEvent $event
     * @return void
     */
    public function onTokenExpired(SpotifyTokenExpiredEvent $event): void
    {
        $userTokenAt = $event->getAccessToken();
        $userToken = $this->userTokenRepository->findOneBy(['access_token' => $userTokenAt]);
        if (empty($userToken)) {
            throw new JsonException('Could not find token information in database when trying to refresh it');
        }

        // Request new token
        $spotifyAuthCredentials = new SpotifyAuthCredentials(
            $_ENV['SPOTIFY_CLIENT_ID'],
            $_ENV['SPOTIFY_CLIENT_SECRET']
        );
        $response = $this->spotifyAuthenticator->renewToken(
            $spotifyAuthCredentials,
            $userToken->getRefreshToken()
        );
        try {
            // Validate response
            $tokenResponse = SpotifyCredentials::fromApiResponse(
                $response['access_token'],
                $response['expires_in'],
                $userToken->getAccessToken()
            );
        } catch (\Exception $e) {
            throw new JsonException('Could not refresh token. Please auth again');
            $this->entityManager->remove($userToken);
            $this->entityManager->flush();
        }

        // Save new token
        $userToken->setAccessToken($tokenResponse->accessToken());
        $userToken->setExpiresIn($tokenResponse->expiresIn());
        $this->entityManager->persist($userToken);
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SpotifyTokenExpiredEvent::class => 'onTokenExpired',
        ];
    }
}
