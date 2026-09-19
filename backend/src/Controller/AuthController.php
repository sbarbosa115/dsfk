<?php

namespace App\Controller;

use App\Dto\ChangePasswordInput;
use App\Entity\User;
use App\Exception\ApiProblem;
use App\Service\CurrentUserPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AuthController extends AbstractController
{
    /** Handled by the json_login authenticator; only reached with a malformed body. */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(['error' => 'invalid_credentials'], 401);
    }

    /** Intercepted by the firewall logout listener. */
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('Handled by the firewall.');
    }

    #[Route('/api/me/password', name: 'api_me_password', methods: ['POST'])]
    public function changePassword(
        #[CurrentUser] User $user,
        #[MapRequestPayload] ChangePasswordInput $input,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        if (!$hasher->isPasswordValid($user, $input->currentPassword)) {
            throw ApiProblem::field('currentPassword', 'La contraseña actual no es correcta.');
        }
        $user->setPassword($hasher->hashPassword($user, $input->newPassword));
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * POST /api/impersonate?_switch_user=<email|_exit> is handled by the firewall (switch_user),
     * which then redirects here without the parameter: the answer is the new current user.
     */
    #[Route('/api/impersonate', name: 'api_impersonate', methods: ['GET', 'POST'])]
    public function impersonate(#[CurrentUser] User $user, CurrentUserPresenter $presenter): JsonResponse
    {
        return $this->json($presenter->present($user));
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user, CurrentUserPresenter $presenter): JsonResponse
    {
        return $this->json($presenter->present($user));
    }
}
