<?php

namespace App\Controller;

use App\Factory\SpotifyConnectorFactory;
use App\Objects\SpotifyCredentials;
use App\Repository\UserTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SongsStatsController extends AbstractController implements TokenAuthControllerInterface
{
    private SpotifyConnectorFactory $spotifyConnectorFactory;
    private UserTokenRepository $userTokenRepository;

    public function __construct(
        SpotifyConnectorFactory $spotifyConnectorFactory,
        UserTokenRepository $userTokenRepository
    ) {
        $this->spotifyConnectorFactory = $spotifyConnectorFactory;
        $this->userTokenRepository = $userTokenRepository;
    }

    /**
     * Gets song stats for a song id from Spotify
     *
     * @param Request $request HTTP Request
     * @return JsonResponse Json response
     */
    public function getStats(Request $request): JsonResponse
    {
        $songId = $request->query->get('song');
        if (empty($songId)) {
            return $this->json(['error' => 'Please provide a song ID'], 400);
        }
        $user = $request->attributes->get('authenticated_user');
        $userToken = $this->userTokenRepository->findOneBy(['user' => $user->getId()]);
        if (empty($userToken)) {
            return $this->json(['error' => 'Invalid user credentials, please auth again'], 400);
        }

        $credentials = SpotifyCredentials::fromUserToken($userToken);
        $connector = $this->spotifyConnectorFactory->create($credentials);
        $response = $connector->getSongStats($songId);

        return $this->json($response);
    }
}
