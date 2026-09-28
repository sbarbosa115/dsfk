<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Controller;

use App\Identity\Application\Command\CreateUser;
use App\Identity\Application\Command\UpdateUser;
use App\Identity\Application\Port\MembershipDirectory;
use App\Identity\Application\Query\UserQueries;
use App\Identity\UI\Http\Input\CreateUserInput;
use App\Identity\UI\Http\Input\UpdateUserInput;
use App\Identity\UI\Http\Output\UserOutput;
use App\Identity\UI\Http\Output\UserPageOutput;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\NewId;
use App\Shared\Application\Security\Actor;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Users, managed by admins. */
#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Users')]
#[OA\Response(response: 403, description: 'Not an admin', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class UserController extends AbstractController
{
    /**
     * @param UserProviderInterface<UserInterface> $userProvider
     */
    public function __construct(
        private readonly CommandBus $bus,
        private readonly UserQueries $users,
        private readonly MembershipDirectory $memberships,
        private readonly TokenStorageInterface $tokens,
        #[Autowire(service: 'security.user.provider.concrete.app_users')] private readonly UserProviderInterface $userProvider,
    ) {
    }

    /** Users with their project roles (the users table and the "Ver como" menu), sorted by name. */
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'One page of users', content: new Model(type: UserPageOutput::class))]
    public function list(
        #[MapQueryParameter] ?string $q = null,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => '/^(active|inactive|all)$/'])] string $status = 'active',
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[MapQueryParameter(options: ['min_range' => 1, 'max_range' => 200])] int $perPage = 50,
    ): JsonResponse {
        $result = $this->users->page($q, $status, $page, $perPage);
        $memberships = $this->memberships->ofAllUsers();

        return $this->json(new UserPageOutput(
            array_map(static fn ($user): UserOutput => UserOutput::from($user, $memberships[(int) $user->getId()] ?? []), $result->items),
            $result->total,
            $page,
            $perPage,
        ));
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The new user', content: new Model(type: UserOutput::class))]
    #[OA\Response(response: 422, description: 'validation_failed or email_taken', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(#[CurrentUser] Actor $actor, #[MapRequestPayload] CreateUserInput $input): JsonResponse
    {
        $id = $this->bus->dispatch(new CreateUser($actor->getId(), $input->email, $input->fullName, $input->password, $input->admin, $input->superAdmin));
        \assert($id instanceof NewId);

        return $this->present($id->value(), Response::HTTP_CREATED);
    }

    /** Edit a user. Only the fields sent change. */
    #[Route('/{id}', name: 'api_users_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'The user', content: new Model(type: UserOutput::class))]
    #[OA\Response(response: 404, description: 'user_not_found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed, email_taken or cannot_change_own_access', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id, #[CurrentUser] Actor $actor, #[MapRequestPayload] UpdateUserInput $input): JsonResponse
    {
        $this->bus->dispatch(new UpdateUser($actor->getId(), $id, $input->email, $input->fullName, $input->password, $input->admin, $input->superAdmin, $input->active));
        $token = $this->tokens->getToken();
        if ($id === $actor->getId() && null !== $token?->getUser()) {
            // An admin who resets their own password here stays signed in (see AuthController::changePassword).
            $token->setUser($this->userProvider->refreshUser($token->getUser()));
        }

        return $this->present($id);
    }

    private function present(int $id, int $status = Response::HTTP_OK): JsonResponse
    {
        $user = $this->users->byId($id) ?? throw $this->createNotFoundException('user_not_found');

        return $this->json(UserOutput::from($user, $this->memberships->ofUser($id)), $status);
    }
}
