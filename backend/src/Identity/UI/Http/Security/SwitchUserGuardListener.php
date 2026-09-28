<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Security;

use App\Shared\UI\Http\ApiCsrfListener;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Switching user changes the session, so a plain link or image (?_switch_user=… in a GET) must not trigger it:
 * only a POST carrying the API CSRF header may. Runs before the firewall (8).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class SwitchUserGuardListener
{
    private const PARAMETER = '_switch_user';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest()) {
            return;
        }
        if (!$request->query->has(self::PARAMETER) && !$request->request->has(self::PARAMETER) && !$request->headers->has(self::PARAMETER)) {
            return;
        }

        if ('POST' !== $request->getMethod() || 'XMLHttpRequest' !== $request->headers->get(ApiCsrfListener::HEADER)) {
            $event->setResponse(new JsonResponse(['error' => 'switch_user_not_allowed'], Response::HTTP_FORBIDDEN));
        }
    }
}
