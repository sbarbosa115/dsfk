<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Stable codes let the Spanish UI pick its message. An unknown email and a wrong password get the same answer,
 * so the login does not reveal who has an account.
 */
final readonly class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $code = match (true) {
            $exception instanceof TooManyLoginAttemptsAuthenticationException => 'too_many_attempts',
            $exception instanceof CustomUserMessageAccountStatusException => 'account_disabled',
            default => 'invalid_credentials',
        };

        // Who tried and from where, never the password (A09).
        $this->logger->warning('Failed sign-in ({code}) for {email} from {ip}.', [
            'code' => $code,
            'email' => mb_substr((string) $request->getPayload()->get('email', ''), 0, 180),
            'ip' => $request->getClientIp(),
        ]);

        return new JsonResponse(['error' => $code], Response::HTTP_UNAUTHORIZED);
    }
}
