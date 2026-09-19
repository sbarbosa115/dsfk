<?php

namespace App\EventListener;

use App\Exception\ApiProblem;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Renders every /api error as JSON: {"error": "...", "violations": {"field": ["..."]}}.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
class ApiExceptionListener
{
    public function __construct(private readonly bool $debug = false)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        // Access denials are turned into 401/403 by the security layer first.
        if ($exception instanceof AccessDeniedException) {
            return;
        }

        // Entities guard their invariants with DomainException('some_code').
        if ($exception instanceof \DomainException) {
            $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], 422));

            return;
        }

        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $data = ['error' => $this->publicMessage($exception->getMessage(), $status)];
        if ($exception instanceof ApiProblem) {
            $data += $exception->getExtra();
        }

        $validation = $exception instanceof ValidationFailedException ? $exception : $exception->getPrevious();
        if ($validation instanceof ValidationFailedException) {
            $data['error'] = 'validation_failed';
            foreach ($validation->getViolations() as $violation) {
                $data['violations'][$violation->getPropertyPath()][] = $violation->getMessage();
            }
        }

        $event->setResponse(new JsonResponse($data, $status));
    }

    /**
     * Our own errors are stable snake_case codes the UI translates. Framework messages can
     * reveal internals (class names, paths), so outside debug they become a generic code.
     */
    private function publicMessage(string $message, int $status): string
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $message) || $this->debug) {
            return $message;
        }

        return match ($status) {
            400 => 'bad_request',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            413 => 'payload_too_large',
            415 => 'unsupported_media_type',
            422 => 'invalid_request',
            default => $status >= 500 ? 'server_error' : 'request_error',
        };
    }
}
