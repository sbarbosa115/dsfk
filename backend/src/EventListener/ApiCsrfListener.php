<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Session-cookie auth needs CSRF protection. Browsers cannot send a custom header
 * cross-origin without a CORS preflight (which we never allow), so requiring it on
 * state-changing API calls blocks cross-site request forgery.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
class ApiCsrfListener
{
    public const HEADER = 'X-Requested-With';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()
            || $request->isMethodSafe()
            || !str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if ('XMLHttpRequest' !== $request->headers->get(self::HEADER)) {
            $event->setResponse(new JsonResponse(['error' => 'csrf_header_missing'], Response::HTTP_FORBIDDEN));
        }
    }
}
