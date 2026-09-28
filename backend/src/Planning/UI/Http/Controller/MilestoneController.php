<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Controller;

use App\Planning\Application\Command\CompleteMilestone;
use App\Planning\Application\Command\DeleteMilestone;
use App\Planning\Application\Command\ReopenMilestone;
use App\Planning\Application\Command\UpdateMilestone;
use App\Planning\UI\Http\Input\CompleteMilestoneInput;
use App\Planning\UI\Http\Input\UpdateMilestoneInput;
use App\Planning\UI\Http\Output\PlanOutput;
use App\Shared\Application\Security\Actor;
use App\Shared\Domain\Money\Percent;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/milestones/{id}', requirements: ['id' => '\d+'])]
#[OA\Tag(name: 'Plan')]
#[OA\Response(response: 200, description: 'The refreshed plan', content: new Model(type: PlanOutput::class))]
#[OA\Response(response: 409, description: 'budget_locked, budget_not_approved, milestone_already_completed, stage_completed', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class MilestoneController extends PlanEndpoint
{
    /** While the budget is editable. Fields sent change; null clears the planned date. */
    #[Route('', name: 'api_milestones_update', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload] UpdateMilestoneInput $input, Request $request): JsonResponse
    {
        $project = $this->project($id, ProjectPermission::PLAN);
        $this->bus->dispatch(new UpdateMilestone(
            $id,
            array_keys($request->getPayload()->all()),
            $input->name,
            null === $input->weight ? null : Percent::toBasisPoints($input->weight),
            self::date($input->plannedDate),
        ));

        return $this->plan($project);
    }

    #[Route('', name: 'api_milestones_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $project = $this->project($id, ProjectPermission::PLAN);
        $this->bus->dispatch(new DeleteMilestone($id));

        return $this->plan($project);
    }

    /** PM or Admin, once the budget is approved. */
    #[Route('/complete', name: 'api_milestones_complete', methods: ['POST'])]
    public function complete(int $id, #[CurrentUser] Actor $actor, #[MapRequestPayload] CompleteMilestoneInput $input): JsonResponse
    {
        $project = $this->project($id, ProjectPermission::PLAN);
        $this->bus->dispatch(new CompleteMilestone($id, $actor->getId(), new \DateTimeImmutable($input->completedAt), $input->notes));

        return $this->plan($project);
    }

    /** Admin only: undoes a completion. */
    #[Route('/reopen', name: 'api_milestones_reopen', methods: ['POST'])]
    public function reopen(int $id): JsonResponse
    {
        $project = $this->project($id, ProjectPermission::ADMINISTER);
        $this->bus->dispatch(new ReopenMilestone($id));

        return $this->plan($project);
    }

    /**
     * @param ProjectPermission::* $permission
     */
    private function project(int $milestoneId, string $permission): int
    {
        $project = $this->known($this->parts->projectOfMilestone($milestoneId), 'milestone_not_found');
        $this->guard($permission, $project);

        return $project;
    }
}
