<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Controller;

use App\Planning\Application\Query\PlanQueries;
use App\Planning\UI\Http\Presenter\PlanPresenter;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Domain\Error\NotFound;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * What every plan endpoint does: find the project a part belongs to, check access (404 outside the project, 403
 * for a role that may not), run the command, and answer with the whole refreshed plan, so the UI never
 * recomputes totals or progress itself.
 */
abstract class PlanEndpoint extends AbstractController
{
    protected CommandBus $bus;
    protected PlanQueries $parts;
    private PlanPresenter $presenter;
    private ProjectGuard $guard;

    #[Required]
    public function setPlanDependencies(CommandBus $bus, PlanQueries $parts, PlanPresenter $presenter, ProjectGuard $guard): void
    {
        $this->bus = $bus;
        $this->parts = $parts;
        $this->presenter = $presenter;
        $this->guard = $guard;
    }

    /**
     * @param ProjectPermission::* $permission
     */
    protected function guard(string $permission, int $projectId): void
    {
        $this->guard->require($permission, $projectId);
    }

    /**
     * @throws NotFound
     */
    protected function known(?int $projectId, string $notFound): int
    {
        return $projectId ?? throw new NotFound($notFound);
    }

    protected function plan(int $projectId): JsonResponse
    {
        return $this->json($this->presenter->present($projectId));
    }

    protected static function date(?string $value): ?\DateTimeImmutable
    {
        return null === $value ? null : new \DateTimeImmutable($value);
    }
}
