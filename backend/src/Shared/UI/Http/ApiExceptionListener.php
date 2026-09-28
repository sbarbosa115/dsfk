<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use App\Shared\Domain\Error\DomainError;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Renders every /api error as JSON: {"error": "code", "violations": {"field": ["message"]}, ...extra}.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
final readonly class ApiExceptionListener
{
    public function __construct(#[Autowire('%kernel.debug%')] private bool $debug)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        // The security layer turns access denials into 401/403 first.
        if ($exception instanceof AccessDeniedException) {
            return;
        }

        if ($exception instanceof DomainError) {
            $event->setResponse(new JsonResponse(['error' => $exception->errorCode] + $exception->extra, $exception->httpStatus()));

            return;
        }

        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $data = ['error' => $this->publicCode($exception->getMessage(), $status)];
        if ($this->debug && $data['error'] !== $exception->getMessage()) {
            $data['detail'] = $exception->getMessage();
        }

        $validation = $exception instanceof ValidationFailedException ? $exception : $exception->getPrevious();
        if ($validation instanceof ValidationFailedException) {
            $data['error'] = 'validation_failed';
            foreach ($validation->getViolations() as $violation) {
                $data['violations'][$violation->getPropertyPath()][] = (string) $violation->getMessage();
            }
        }

        $event->setResponse(new JsonResponse($data, $status));
    }

    /**
     * A snake_case message is already a code. Framework messages can reveal internals (class names, paths), so
     * they become a generic code for their status.
     */
    private function publicCode(string $message, int $status): string
    {
        if (1 === preg_match('/^[a-z][a-z0-9_]*$/', $message)) {
            return $message;
        }

        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthorized',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            413 => 'payload_too_large',
            415 => 'unsupported_media_type',
            422 => 'invalid_request',
            429 => 'too_many_attempts',
            default => $status >= 500 ? 'server_error' : 'request_error',
        };
    }
}
