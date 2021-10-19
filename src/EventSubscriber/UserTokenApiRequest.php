<?php

namespace App\EventSubscriber;

use App\Controller\TokenAuthControllerInterface;
use App\Exception\JsonException;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserTokenApiRequest implements EventSubscriberInterface
{

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Checks if the request comes with an Auth token, and if it does then stores the user
     *
     * @param ControllerEvent $event
     * @return void
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof TokenAuthControllerInterface) {
            $request = $event->getRequest();
            $token = $request->headers->get('Authorization');
            if (empty($token) || substr($token, 0, 7) !== 'bearer ') {
                throw new JsonException('Auth yourself first', 400);
            }

            $token = explode('bearer ', $token);
            $user = $this->userRepository->findOneBy(['login_token' => $token[1]]);
            if (empty($user)) {
                throw new JsonException('Invalid token', 400);
            }
            $request->attributes->set('authenticated_user', $user);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
