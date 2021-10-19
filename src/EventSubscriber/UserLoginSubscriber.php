<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserToken;
use App\Event\UserLoggedInEvent;
use App\Exception\JsonException;
use App\Factory\SpotifyConnectorFactory;
use App\Objects\SpotifyCredentials;
use App\Repository\UserRepository;
use App\Repository\UserTokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserLoginSubscriber implements EventSubscriberInterface
{

    private UserTokenRepository $userTokenRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SpotifyConnectorFactory $spotifyConnectorFactory;

    public function __construct(
        UserTokenRepository $userTokenRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SpotifyConnectorFactory $spotifyConnectorFactory
    ) {
        $this->userTokenRepository = $userTokenRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->spotifyConnectorFactory = $spotifyConnectorFactory;
    }

    /**
     * Stores new user or updates its token when an user is logged in
     *
     * @param UserLoggedInEvent $event
     * @return void
     */
    public function onUserLogin(UserLoggedInEvent $event): void
    {
        $spotifyCredentials = $event->getCredentials();
        $spotifyConnector = $this->spotifyConnectorFactory->create($spotifyCredentials);

        $userData = $spotifyConnector->getUser();
        if (empty($userData['email'])) {
            return;
        }

        $dbUser = $this->userRepository->findOneBy(['email' => $userData['email']]);
        if (empty($dbUser)) {
            $this->storeNewUserWithCredentials($userData['email'], $spotifyCredentials);
        } else {
            $this->updateCredentials($dbUser, $spotifyCredentials);
        }
    }

    /**
     * Stores a new user with its credential in the database
     *
     * @param string $userEmail user email
     * @param SpotifyCredentials $spotifyCredentials user credentials
     * @return void
     */
    private function storeNewUserWithCredentials(string $userEmail, SpotifyCredentials $spotifyCredentials): void
    {
        $user = new User();
        $user->setCreatedAt(new DateTime());
        $user->setEmail($userEmail);
        // Since we are not using a real password, we can use a "weak" random string generator here
        $user->setLoginToken(md5(random_bytes(31)));

        $token = new UserToken();
        $token->setAccessToken($spotifyCredentials->accessToken());
        $token->setExpiresIn($spotifyCredentials->expiresIn());
        $token->setRefreshToken($spotifyCredentials->refreshToken());
        $token->setUser($user);
        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    /**
     * Updates credentials for a given user in the database
     *
     * @param User $user user to update
     * @param SpotifyCredentials $spotifyCredentials new credentials to store
     * @return void
     */
    private function updateCredentials(User $user, SpotifyCredentials $spotifyCredentials): void
    {
        $token = $this->userTokenRepository->findOneBy(['user' => $user->getId()]);
        if (empty($token)) {
            $token = new UserToken();
            $token->setUser($user);
        }

        $token->setAccessToken($spotifyCredentials->accessToken());
        $token->setExpiresIn($spotifyCredentials->expiresIn());
        $token->setRefreshToken($spotifyCredentials->refreshToken());
        $this->entityManager->persist($token);

        // New login, discard old login token.
        $user->setLoginToken(md5(random_bytes(31)));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserLoggedInEvent::class => 'onUserLogin',
        ];
    }
}
