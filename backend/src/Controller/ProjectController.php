<?php

namespace App\Controller;

use App\Dto\MemberInput;
use App\Dto\ProjectInput;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Enum\ProjectRole;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Security\ProjectVoter;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    private const LIST_CONTEXT = ['groups' => ['project:read']];
    private const DETAIL_CONTEXT = ['groups' => ['project:read', 'project:detail', 'member:read']];

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] User $user, ProjectRepository $projects): JsonResponse
    {
        return $this->json($projects->findVisibleTo($user), context: self::LIST_CONTEXT);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload(validationGroups: ['Default', 'create'])] ProjectInput $input,
        SettingsService $settings,
    ): JsonResponse {
        $project = new Project($input->name, $input->currency ?? $settings->get(SettingsService::DEFAULT_CURRENCY));
        $this->apply($project, $input);
        $this->em->persist($project);
        $this->em->flush();

        return $this->json($project, 201, context: self::DETAIL_CONTEXT);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW, 'project')]
    public function show(Project $project): JsonResponse
    {
        return $this->json($project, context: self::DETAIL_CONTEXT);
    }

    #[Route('/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::ADMINISTER, 'project')]
    public function update(Project $project, #[MapRequestPayload] ProjectInput $input): JsonResponse
    {
        if (null !== $input->currency && $input->currency !== $project->getCurrency()) {
            throw new UnprocessableEntityHttpException('currency_locked');
        }
        $this->apply($project, $input);
        $this->em->flush();

        return $this->json($project, context: self::DETAIL_CONTEXT);
    }

    #[Route('/{id}/members', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::ADMINISTER, 'project')]
    public function addMember(Project $project, #[MapRequestPayload] MemberInput $input, UserRepository $users): JsonResponse
    {
        $user = $users->find($input->userId) ?? throw new UnprocessableEntityHttpException('user_not_found');
        if ($user->isAdmin()) {
            throw new UnprocessableEntityHttpException('admin_is_global');
        }

        $member = $project->findMember($user);
        $this->assertSingleProjectManager($project, $input->role, $member);

        if (null === $member) {
            $member = new ProjectMember($project, $user, $input->role);
            $project->addMember($member);
        } else {
            $member->setRole($input->role);
        }
        $this->em->flush();

        return $this->json($project, context: self::DETAIL_CONTEXT);
    }

    #[Route('/{id}/members/{memberId}', methods: ['DELETE'], requirements: ['id' => '\d+', 'memberId' => '\d+'])]
    #[IsGranted(ProjectVoter::ADMINISTER, 'project')]
    public function removeMember(Project $project, int $memberId): Response
    {
        $member = $project->getMembers()->findFirst(static fn ($k, ProjectMember $m) => $m->getId() === $memberId)
            ?? throw new NotFoundHttpException('member_not_found');
        $project->removeMember($member);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function apply(Project $project, ProjectInput $input): void
    {
        if (null !== $input->name) {
            $project->setName($input->name);
        }
        if (null !== $input->description) {
            $project->setDescription('' === trim($input->description) ? null : $input->description);
        }
        if (null !== $input->status) {
            $project->setStatus($input->status);
        }
        if (null !== $input->plannedStart) {
            $project->setPlannedStart($input->plannedStart);
        }
        if (null !== $input->plannedEnd) {
            $project->setPlannedEnd($input->plannedEnd);
        }
    }

    /** The PM holds the project's caja menor, so a project has at most one. */
    private function assertSingleProjectManager(Project $project, ProjectRole $role, ?ProjectMember $current): void
    {
        if (ProjectRole::ProjectManager !== $role) {
            return;
        }
        foreach ($project->getMembers() as $member) {
            if ($member !== $current && ProjectRole::ProjectManager === $member->getRole()) {
                throw new UnprocessableEntityHttpException('project_manager_exists');
            }
        }
    }
}
