<?php

declare(strict_types=1);

namespace App\Reporting\UI\Http\Controller;

use App\Reporting\Application\Port\ProjectSource;
use App\Reporting\Application\Service\Dashboards;
use App\Reporting\UI\Http\Output\PortfolioRowOutput;
use App\Reporting\UI\Http\Output\ProjectDashboardOutput;
use App\Reporting\UI\Http\Presenter\DashboardPresenter;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Dashboards')]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly Dashboards $dashboards, private readonly ProjectSource $projects, private readonly ProjectGuard $guard)
    {
    }

    /** Every project whose money the person may see (all for Admins, their own for a PM), by name. */
    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The portfolio', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: PortfolioRowOutput::class))))]
    public function portfolio(): JsonResponse
    {
        $visible = array_values(array_filter(
            array_column($this->projects->all(), 'id'),
            fn (int $id): bool => $this->guard->allows(ProjectPermission::VIEW_FINANCIALS, $id),
        ));

        return $this->json(array_map(DashboardPresenter::row(...), $this->dashboards->portfolio($visible)));
    }

    #[Route('/api/projects/{id}/dashboard', name: 'api_project_dashboard', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The project dashboard', content: new Model(type: ProjectDashboardOutput::class))]
    #[OA\Response(response: 403, description: 'forbidden: Team Leads see no money', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'project_not_found (also outside the project)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function project(int $id): JsonResponse
    {
        $this->guard->require(ProjectPermission::VIEW_FINANCIALS, $id);

        return $this->json(DashboardPresenter::project($this->dashboards->project($id)));
    }
}
