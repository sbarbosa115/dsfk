<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Controller;

use App\Identity\Application\Command\CreateUser;
use App\Identity\Application\Command\UpdateUser;
use App\Identity\Application\Port\MembershipDirectory;
use App\Identity\Application\Query\UserQueries;
use App\Identity\UI\Http\Input\UserInput;
use App\Identity\UI\Http\Output\UserOutput;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\NewId;
use App\Shared\Application\Security\Actor;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Users, managed by admins. */
#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Users')]
#[OA\Response(response: 403, description: 'Not an admin', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly UserQueries $users,
        private readonly MembershipDirectory $memberships,
    ) {
    }

    /** Every user with their project roles (the users table and the "Ver como" menu). */
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Users sorted by name', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: UserOutput::class))))]
    public function list(): JsonResponse
    {
        $memberships = $this->memberships->ofAllUsers();

        return $this->json(array_map(
            static fn ($user): UserOutput => UserOutput::from($user, $memberships[(int) $user->getId()] ?? []),
            $this->users->all(),
        ));
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The new user', content: new Model(type: UserOutput::class))]
    #[OA\Response(response: 422, description: 'validation_failed or email_taken', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(#[CurrentUser] Actor $actor, #[MapRequestPayload(validationGroups: ['Default', 'create'])] UserInput $input): JsonResponse
    {
        $id = $this->bus->dispatch(new CreateUser(
            $actor->getId(),
            (string) $input->email,
            (string) $input->fullName,
            (string) $input->password,
            $input->admin ?? false,
            $input->superAdmin ?? false,
        ));
        \assert($id instanceof NewId);

        return $this->present($id->value(), Response::HTTP_CREATED);
    }

    /** Edit a user. Only the fields sent change. */
    #[Route('/{id}', name: 'api_users_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'The user', content: new Model(type: UserOutput::class))]
    #[OA\Response(response: 404, description: 'user_not_found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed, email_taken or cannot_change_own_access', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id, #[CurrentUser] Actor $actor, #[MapRequestPayload] UserInput $input): JsonResponse
    {
        $this->bus->dispatch(new UpdateUser($actor->getId(), $id, $input->email, $input->fullName, $input->password, $input->admin, $input->superAdmin, $input->active));

        return $this->present($id);
    }

    private function present(int $id, int $status = Response::HTTP_OK): JsonResponse
    {
        $user = $this->users->byId($id) ?? throw $this->createNotFoundException('user_not_found');

        return $this->json(UserOutput::from($user, $this->memberships->ofUser($id)), $status);
    }
}
