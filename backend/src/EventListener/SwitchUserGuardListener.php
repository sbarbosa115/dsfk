<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Switching user changes the session, so it must not be triggered by a plain link or
 * image (?_switch_user=... in a GET). Only a POST carrying the API CSRF header may do it.
 * Runs before the firewall (priority 8).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
class SwitchUserGuardListener
{
    private const PARAMETER = '_switch_user';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest()) {
            return;
        }
        $requested = $request->query->has(self::PARAMETER) || $request->request->has(self::PARAMETER) || $request->headers->has(self::PARAMETER);
        if (!$requested) {
            return;
        }

        if ('POST' !== $request->getMethod() || 'XMLHttpRequest' !== $request->headers->get(ApiCsrfListener::HEADER)) {
            $event->setResponse(new JsonResponse(['error' => 'switch_user_not_allowed'], Response::HTTP_FORBIDDEN));
        }
    }
}
