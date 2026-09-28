<?php

declare(strict_types=1);

namespace App\Audit\UI\Http\Controller;

use App\Audit\Application\Query\AuditQueries;
use App\Audit\Application\Query\ProjectNames;
use App\Audit\Domain\Model\AuditRecord;
use App\Audit\UI\Http\Output\AuditEntryOutput;
use App\Audit\UI\Http\Output\AuditPageOutput;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Audit')]
final class AuditController extends AbstractController
{
    public function __construct(private readonly AuditQueries $audit, private readonly ProjectNames $projects)
    {
    }

    /** Who changed what, newest first: Admins only. `q` searches the person's name. */
    #[Route('/api/audit', name: 'api_audit', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(response: 200, description: 'One page of the audit trail', content: new Model(type: AuditPageOutput::class))]
    #[OA\Response(response: 403, description: 'Not an admin', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function list(
        #[MapQueryParameter] ?int $projectId = null,
        #[MapQueryParameter] ?string $entityType = null,
        #[MapQueryParameter] ?string $q = null,
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[MapQueryParameter(options: ['min_range' => 1, 'max_range' => 100])] int $perPage = 50,
    ): JsonResponse {
        $result = $this->audit->page($projectId, $entityType, $q, $page, $perPage);
        $names = $this->projects->names(array_values(array_unique(array_filter(array_map(static fn (AuditRecord $r): ?int => $r->getProjectId(), $result->items)))));

        return $this->json(new AuditPageOutput(
            array_map(static fn (AuditRecord $r): AuditEntryOutput => new AuditEntryOutput(
                $r->getId(),
                $r->getProjectId(),
                null === $r->getProjectId() ? null : ($names[$r->getProjectId()] ?? null),
                $r->getUserName(),
                $r->getAction(),
                $r->getEntityType(),
                $r->getEntityId(),
                $r->getChanges(),
                $r->getCreatedAt()->format(\DATE_ATOM),
            ), $result->items),
            $result->total,
            $page,
            $perPage,
            $this->audit->entityTypes(),
        ));
    }
}
