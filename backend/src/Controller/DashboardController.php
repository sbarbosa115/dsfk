<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Security\ProjectVoter;
use App\Service\DashboardService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    /** Portfolio: every project whose finances the user may see. */
    #[Route('/api/dashboard', methods: ['GET'])]
    public function portfolio(#[CurrentUser] User $user, ProjectRepository $projects): JsonResponse
    {
        $rows = [];
        foreach ($projects->findVisibleTo($user) as $project) {
            if ($this->isGranted(ProjectVoter::VIEW_FINANCIALS, $project)) {
                $rows[] = $this->dashboard->summary($project);
            }
        }

        return $this->json($rows);
    }

    #[Route('/api/projects/{id}/dashboard', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW_FINANCIALS, 'project')]
    public function project(Project $project): JsonResponse
    {
        return $this->json($this->dashboard->project($project));
    }

    #[Route('/api/audit', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function audit(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $perPage = 50;
        $page = max(1, $request->query->getInt('page', 1));
        $qb = $em->createQueryBuilder()->select('a')->from(AuditLog::class, 'a')->orderBy('a.id', 'DESC');
        if ($projectId = $request->query->getInt('projectId')) {
            $qb->andWhere('a.projectId = :project')->setParameter('project', $projectId);
        }
        if ($type = $request->query->get('entityType')) {
            $qb->andWhere('a.entityType = :type')->setParameter('type', $type);
        }
        $total = (int) (clone $qb)->select('COUNT(a.id)')->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        /** @var list<AuditLog> $items */
        $items = $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage)->getQuery()->getResult();

        return $this->json([
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'items' => array_map(static fn (AuditLog $a) => [
                'id' => $a->getId(),
                'projectId' => $a->getProjectId(),
                'user' => $a->getUserName(),
                'action' => $a->getAction(),
                'entityType' => $a->getEntityType(),
                'entityId' => $a->getEntityId(),
                'changes' => $a->getChanges(),
                'createdAt' => $a->getCreatedAt()->format(\DATE_ATOM),
            ], $items),
        ]);
    }
}
