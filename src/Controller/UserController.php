<?php

namespace App\Controller;

use App\Factory\SpotifyConnectorFactory;
use App\Objects\SpotifyCredentials;
use App\Repository\UserTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController implements TokenAuthControllerInterface
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
     * Gets current playing status from Spotify
     *
     * @param Request $request HTTP Request
     * @return JsonResponse Json response
     */
    public function currentPlaying(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $userToken = $this->userTokenRepository->findOneBy(['user' => $user->getId()]);
        if (empty($userToken)) {
            return $this->json(['error' => 'Invalid user credentials, please auth again'], 400);
        }

        $credentials = SpotifyCredentials::fromUserToken($userToken);
        $connector = $this->spotifyConnectorFactory->create($credentials);
        $response = $connector->getCurrentPlay();

        return $this->json($response);
    }
}
