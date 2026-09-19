<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Stable codes let the Spanish UI pick its own message.
        $code = match (true) {
            $exception instanceof TooManyLoginAttemptsAuthenticationException => 'too_many_attempts',
            $exception instanceof CustomUserMessageAccountStatusException => 'account_disabled',
            default => 'invalid_credentials',
        };

        return new JsonResponse(['error' => $code], Response::HTTP_UNAUTHORIZED);
    }
}
