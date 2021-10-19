<?php

namespace App\EventListener;

use App\Exception\JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // the priority must be greater than the Security HTTP
            // ExceptionListener, to make sure it's called before
            // the default exception listener
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof JsonException) {
            return;
        }

        $responseCode = $exception->getCode();
        if (!$responseCode) {
            $responseCode = 400;
        }
        $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], $responseCode));

        // or stop propagation (prevents the next exception listeners from being called)
        //$event->stopPropagation();
    }
}
