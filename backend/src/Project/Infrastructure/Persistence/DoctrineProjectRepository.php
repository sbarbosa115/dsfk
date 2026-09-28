<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Persistence;

use App\Project\Application\Query\MembershipQueries;
use App\Project\Application\Query\ProjectAccess;
use App\Project\Application\Query\ProjectDirectory;
use App\Project\Application\Query\ProjectInfo;
use App\Project\Application\Query\ProjectQueries;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectMember;
use App\Project\Domain\Model\ProjectRole;
use App\Project\Domain\Model\ProjectStatus;
use App\Project\Domain\Repository\ProjectRepository;
use App\Shared\Application\Query\Page;
use App\Shared\Domain\Error\NotFound;
use App\Shared\Infrastructure\Doctrine\Search;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final readonly class DoctrineProjectRepository implements ProjectRepository, ProjectQueries, ProjectAccess, MembershipQueries, ProjectDirectory
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function get(int $id): Project
    {
        return $this->em->find(Project::class, $id) ?? throw new NotFound('project_not_found');
    }

    public function add(Project $project): void
    {
        $this->em->persist($project);
    }

    public function page(?int $memberId, ?string $search, ?ProjectStatus $status, int $page, int $perPage): Page
    {
        $qb = $this->em->createQueryBuilder()
            ->select('p', 'm')
            ->from(Project::class, 'p')
            ->leftJoin('p.members', 'm')
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');
        if (null !== $memberId) {
            $qb->andWhere('EXISTS (SELECT 1 FROM '.ProjectMember::class.' mine WHERE mine.project = p AND mine.userId = :member)')
                ->setParameter('member', $memberId);
        }
        if (null !== $status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        Search::apply($qb, $search, ['p.name']);
        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $paginator = new Paginator($qb, fetchJoinCollection: true);

        return new Page(array_values(iterator_to_array($paginator)), \count($paginator));
    }

    public function byId(int $id): ?Project
    {
        return $this->em->find(Project::class, $id);
    }

    public function roleOf(int $projectId, int $userId, bool $isAdmin): ?string
    {
        $project = $this->em->find(Project::class, $projectId);
        if (null === $project) {
            return null;
        }

        return $isAdmin ? self::ADMIN : $project->roleOf($userId)?->value;
    }

    public function ofUser(int $userId): array
    {
        return $this->memberships($userId)[$userId] ?? [];
    }

    public function ofAllUsers(): array
    {
        return $this->memberships(null);
    }

    public function info(int $projectId): ProjectInfo
    {
        $project = $this->get($projectId);

        return new ProjectInfo((int) $project->getId(), $project->getName(), $project->getCurrency(), $project->getStatus()->value);
    }

    public function projectManagerOf(int $projectId): ?int
    {
        foreach ($this->get($projectId)->getMembers() as $member) {
            if (ProjectRole::ProjectManager === $member->getRole()) {
                return $member->getUserId();
            }
        }

        return null;
    }

    public function active(): array
    {
        $projects = $this->em->getRepository(Project::class)->findBy(['status' => ProjectStatus::Active], ['name' => 'ASC']);

        return array_map(static fn (Project $p): ProjectInfo => new ProjectInfo((int) $p->getId(), $p->getName(), $p->getCurrency(), $p->getStatus()->value), $projects);
    }

    public function all(): array
    {
        $projects = $this->em->getRepository(Project::class)->findBy([], ['name' => 'ASC']);

        return array_map(static fn (Project $p): ProjectInfo => new ProjectInfo((int) $p->getId(), $p->getName(), $p->getCurrency(), $p->getStatus()->value), $projects);
    }

    /**
     * @return array<int, list<array{projectId: int, projectName: string, role: string}>>
     */
    private function memberships(?int $userId): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('m.userId', 'p.id AS projectId', 'p.name AS projectName', 'm.role')
            ->from(ProjectMember::class, 'm')
            ->join('m.project', 'p')
            ->orderBy('p.name', 'ASC');
        if (null !== $userId) {
            $qb->where('m.userId = :user')->setParameter('user', $userId);
        }

        $byUser = [];
        /** @var list<array{userId: int, projectId: int, projectName: string, role: ProjectRole}> $rows */
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $byUser[$row['userId']][] = ['projectId' => $row['projectId'], 'projectName' => $row['projectName'], 'role' => $row['role']->value];
        }

        return $byUser;
    }
}
