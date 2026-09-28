<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Controller;

use App\Project\Application\Command\AssignMember;
use App\Project\Application\Command\CreateProject;
use App\Project\Application\Command\RemoveMember;
use App\Project\Application\Command\UpdateProject;
use App\Project\Application\Query\ProjectQueries;
use App\Project\Domain\Model\ProjectRole;
use App\Project\UI\Http\Input\CreateProjectInput;
use App\Project\UI\Http\Input\MemberInput;
use App\Project\UI\Http\Input\UpdateProjectInput;
use App\Project\UI\Http\Output\ProjectOutput;
use App\Project\UI\Http\Presenter\ProjectPresenter;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\NewId;
use App\Shared\Application\Security\Actor;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/projects')]
#[OA\Tag(name: 'Projects')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly ProjectQueries $projects,
        private readonly ProjectPresenter $presenter,
        private readonly ProjectGuard $guard,
    ) {
    }

    /** Admins see every project; everyone else the projects they belong to. Newest first. */
    #[Route('', name: 'api_projects_list', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'One page of projects; X-Total-Count has the total', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ProjectOutput::class))))]
    public function list(
        #[CurrentUser] Actor $actor,
        #[MapQueryParameter] ?string $q = null,
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[MapQueryParameter(options: ['min_range' => 1, 'max_range' => 100])] int $perPage = 50,
    ): JsonResponse {
        $result = $this->projects->page($actor->isAdmin() ? null : $actor->getId(), $q, $page, $perPage);

        return $this->json($this->presenter->presentMany($result->items, $actor), headers: ['X-Total-Count' => (string) $result->total]);
    }

    #[Route('', name: 'api_projects_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(response: 201, description: 'The new project (DRAFT)', content: new Model(type: ProjectOutput::class))]
    #[OA\Response(response: 403, description: 'Not an admin', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function create(#[CurrentUser] Actor $actor, #[MapRequestPayload] CreateProjectInput $input): JsonResponse
    {
        $id = $this->bus->dispatch(new CreateProject(
            $input->name,
            $input->description,
            $input->currency,
            $input->status,
            self::date($input->plannedStart),
            self::date($input->plannedEnd),
        ));
        \assert($id instanceof NewId);

        return $this->present($id->value(), $actor, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_projects_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The project with its members', content: new Model(type: ProjectOutput::class))]
    #[OA\Response(response: 404, description: 'project_not_found (also when the user is not in it)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function show(int $id, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::VIEW, $id);

        return $this->present($id, $actor);
    }

    /** Admin only. Only the fields sent change. */
    #[Route('/{id}', name: 'api_projects_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[OA\Response(response: 200, description: 'The project', content: new Model(type: ProjectOutput::class))]
    #[OA\Response(response: 403, description: 'forbidden', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'project_not_found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed or currency_locked', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(int $id, #[CurrentUser] Actor $actor, #[MapRequestPayload] UpdateProjectInput $input, Request $request): JsonResponse
    {
        $this->guard->require(ProjectPermission::ADMINISTER, $id);
        $this->bus->dispatch(new UpdateProject(
            $id,
            array_keys($request->getPayload()->all()),
            $input->name,
            $input->description,
            $input->currency,
            $input->status,
            self::date($input->plannedStart),
            self::date($input->plannedEnd),
        ));

        return $this->present($id, $actor);
    }

    /** Admin only. Adds a person, or changes their role if they are already in the project. */
    #[Route('/{id}/members', name: 'api_projects_members_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The project with its members', content: new Model(type: ProjectOutput::class))]
    #[OA\Response(response: 409, description: 'project_manager_exists', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'user_not_found, admin_is_global, user_inactive or validation_failed', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function addMember(int $id, #[CurrentUser] Actor $actor, #[MapRequestPayload] MemberInput $input): JsonResponse
    {
        $this->guard->require(ProjectPermission::ADMINISTER, $id);
        \assert($input->role instanceof ProjectRole);
        $this->bus->dispatch(new AssignMember($id, $input->userId, $input->role));

        return $this->present($id, $actor);
    }

    #[Route('/{id}/members/{memberId}', name: 'api_projects_members_remove', requirements: ['id' => '\d+', 'memberId' => '\d+'], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Removed')]
    #[OA\Response(response: 404, description: 'project_not_found or member_not_found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function removeMember(int $id, int $memberId): Response
    {
        $this->guard->require(ProjectPermission::ADMINISTER, $id);
        $this->bus->dispatch(new RemoveMember($id, $memberId));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function present(int $id, Actor $actor, int $status = Response::HTTP_OK): JsonResponse
    {
        $project = $this->projects->byId($id) ?? throw $this->createNotFoundException('project_not_found');

        return $this->json($this->presenter->present($project, $actor), $status);
    }

    private static function date(?string $value): ?\DateTimeImmutable
    {
        return null === $value ? null : new \DateTimeImmutable($value);
    }
}
