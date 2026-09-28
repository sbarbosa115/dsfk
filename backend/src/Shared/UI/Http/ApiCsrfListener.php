<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Session-cookie auth needs CSRF protection. Browsers cannot send a custom header cross-origin without a CORS
 * preflight (which is never allowed), so requiring it on state-changing API calls blocks cross-site forgery.
 */
// Before routing (32), so an unknown path gets no different answer than a known one.
#[AsEventListener(event: KernelEvents::REQUEST, priority: 64)]
final class ApiCsrfListener
{
    public const HEADER = 'X-Requested-With';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || $request->isMethodSafe() || !str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if ('XMLHttpRequest' !== $request->headers->get(self::HEADER)) {
            $event->setResponse(new JsonResponse(['error' => 'csrf_header_missing'], Response::HTTP_FORBIDDEN));
        }
    }
}
