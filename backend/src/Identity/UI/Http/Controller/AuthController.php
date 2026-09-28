<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Controller;

use App\Identity\Application\Command\ChangeOwnPassword;
use App\Identity\UI\Http\Input\ChangePasswordInput;
use App\Identity\UI\Http\Output\CurrentUserOutput;
use App\Identity\UI\Http\Presenter\CurrentUserPresenter;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Security\Actor;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Auth')]
final class AuthController extends AbstractController
{
    /**
     * @param UserProviderInterface<UserInterface> $users
     */
    public function __construct(
        private readonly CurrentUserPresenter $presenter,
        private readonly TokenStorageInterface $tokens,
        #[Autowire(service: 'security.user.provider.concrete.app_users')] private readonly UserProviderInterface $users,
    ) {
    }

    /** Sign in with email and password (session cookie). Handled by the json_login authenticator. */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\JsonContent(required: ['email', 'password'], properties: [
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'password', type: 'string'),
    ]))]
    #[OA\Response(response: 200, description: 'The signed-in user', content: new Model(type: CurrentUserOutput::class))]
    #[OA\Response(response: 401, description: 'invalid_credentials, account_disabled or too_many_attempts', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function login(): JsonResponse
    {
        // Only reached when the body is not a JSON login (the authenticator skips it).
        return $this->json(['error' => 'invalid_credentials'], Response::HTTP_UNAUTHORIZED);
    }

    /** Sign out. Intercepted by the firewall's logout listener. */
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    #[OA\Response(response: 204, description: 'Signed out')]
    public function logout(): never
    {
        throw new \LogicException('Handled by the firewall.');
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The signed-in user', content: new Model(type: CurrentUserOutput::class))]
    public function me(#[CurrentUser] Actor $actor): JsonResponse
    {
        return $this->json($this->presenter->present($actor, $this->tokens->getToken()));
    }

    /**
     * "Ver como": POST /api/impersonate?_switch_user=<email|_exit> is handled by the firewall (switch_user), which
     * then redirects here without the parameter; the answer is the new current user.
     */
    #[Route('/api/impersonate', name: 'api_impersonate', methods: ['GET', 'POST'])]
    #[OA\Parameter(name: '_switch_user', in: 'query', required: false, description: 'Email of the user to view as, or _exit')]
    #[OA\Response(response: 200, description: 'The user now signed in', content: new Model(type: CurrentUserOutput::class))]
    #[OA\Response(response: 302, description: 'Switched; follow to GET /api/impersonate')]
    #[OA\Response(response: 403, description: 'switch_user_not_allowed, or not allowed to view as that user', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function impersonate(#[CurrentUser] Actor $actor): JsonResponse
    {
        return $this->json($this->presenter->present($actor, $this->tokens->getToken()));
    }

    #[Route('/api/me/password', name: 'api_me_password', methods: ['POST'])]
    #[OA\Response(response: 204, description: 'Password changed')]
    #[OA\Response(response: 422, description: 'validation_failed (currentPassword wrong, newPassword too short or unchanged)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function changePassword(#[CurrentUser] Actor $actor, #[MapRequestPayload] ChangePasswordInput $input, CommandBus $bus): Response
    {
        $bus->dispatch(new ChangeOwnPassword($actor->getId(), $input->currentPassword, $input->newPassword));

        // The session holds the old hash; without this the next request would see a changed user and sign this
        // session out too. Other sessions still end.
        $token = $this->tokens->getToken();
        if (null !== $token && null !== $token->getUser()) {
            $token->setUser($this->users->refreshUser($token->getUser()));
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
