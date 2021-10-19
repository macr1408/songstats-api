<?php

namespace App\Controller;

use App\Event\UserLoggedInEvent;
use App\Objects\SpotifyAuthCredentials;
use App\Objects\SpotifyCredentials;
use App\Repository\UserTokenRepository;
use App\Service\SpotifyAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthController extends AbstractController
{

    private LoggerInterface $logger;
    private SpotifyAuthenticator $spotifyAuth;
    private SpotifyAuthCredentials $spotifyAuthCredentials;
    private EventDispatcherInterface $eventDispatcherInterface;
    private UserTokenRepository $userTokenRepository;

    public function __construct(
        LoggerInterface $logger,
        SpotifyAuthenticator $spotifyAuthenticator,
        EventDispatcherInterface $eventDispatcherInterface,
        UserTokenRepository $userTokenRepository
    ) {
        $this->logger = $logger;
        $this->spotifyAuth = $spotifyAuthenticator;
        $this->eventDispatcherInterface = $eventDispatcherInterface;
        $this->userTokenRepository = $userTokenRepository;
        $this->spotifyAuthCredentials = new SpotifyAuthCredentials(
            $_ENV['SPOTIFY_CLIENT_ID'],
            $_ENV['SPOTIFY_CLIENT_SECRET']
        );
    }

    /**
     * Gets link for starting Spotify Auth process
     *
     * @param Request $request Http request
     * @return JsonResponse Json response
     */
    public function getAuthLink(Request $request): JsonResponse
    {
        $redirectUri = $request->query->get('redirect');
        if (empty($redirectUri)) {
            return $this->json(['error' => 'Invalid redirect url provided'], 400);
        }
        return $this->json(['url' => $this->spotifyAuth->authLink($this->spotifyAuthCredentials, $redirectUri)]);
    }

    /**
     * Auths user in spotify
     *
     * @param Request $request HTTP Request
     * @return JsonResponse Json response
     */
    public function authUser(Request $request): JsonResponse
    {
        $redirectUri = $request->query->get('redirect');
        if (empty($redirectUri)) {
            return $this->json(['error' => 'Invalid redirect url provided'], 400);
        }
        $authCode = $request->query->get('code');
        if (empty($authCode)) {
            return $this->json(['error' => 'Code not found'], 400);
        }

        // Get token from Spotify
        $apiResponse = $this->spotifyAuth->exchangeCode($this->spotifyAuthCredentials, $authCode, $redirectUri);
        try {
            // Validate response
            $tokenResponse = SpotifyCredentials::fromApiResponse(
                $apiResponse['access_token'],
                $apiResponse['expires_in'],
                $apiResponse['refresh_token']
            );
        } catch (\Exception $e) {
            $this->logger->error(__FUNCTION__ . ': ' . $e->getMessage());
            return $this->json(['error' => 'Invalid response from Spotify API. Please try again.'], 400);
        }

        $this->eventDispatcherInterface->dispatch(new UserLoggedInEvent($tokenResponse));

        $userToken = $this->userTokenRepository->findOneBy(['access_token' => $tokenResponse->accessToken()]);
        if (empty($userToken)) {
            return $this->json(['error' => 'Could not auth user, please try again'], 400);
        }

        return $this->json($userToken->getUser());
    }
}
