<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Security;

use App\Identity\UI\Http\Presenter\CurrentUserPresenter;
use App\Shared\Application\Security\Actor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/** A successful login answers with the signed-in user, like GET /api/me. */
final readonly class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private CurrentUserPresenter $presenter)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        \assert($user instanceof Actor);

        return new JsonResponse($this->presenter->present($user, $token));
    }
}
