<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Controller;

use App\Planning\Application\Command\AddLine;
use App\Planning\Application\Command\AddMilestone;
use App\Planning\Application\Command\DeleteStage;
use App\Planning\Application\Command\StartStage;
use App\Planning\Application\Command\UpdateStage;
use App\Planning\UI\Http\Input\CreateMilestoneInput;
use App\Planning\UI\Http\Input\LineInput;
use App\Planning\UI\Http\Input\StartStageInput;
use App\Planning\UI\Http\Input\UpdateStageInput;
use App\Planning\UI\Http\Output\PlanOutput;
use App\Shared\Domain\Money\Percent;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stages/{id}', requirements: ['id' => '\d+'])]
#[OA\Tag(name: 'Plan')]
#[OA\Response(response: 200, description: 'The refreshed plan', content: new Model(type: PlanOutput::class))]
#[OA\Response(response: 404, description: 'stage_not_found, or project_not_found outside the project', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 409, description: 'budget_locked, budget_not_approved, stage_already_started', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class StageController extends PlanEndpoint
{
    /** The name can always be fixed; planned dates only while the budget is editable. Fields sent change. */
    #[Route('', name: 'api_stages_update', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload] UpdateStageInput $input, Request $request): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new UpdateStage($id, array_keys($request->getPayload()->all()), $input->name, self::date($input->plannedStart), self::date($input->plannedEnd)));

        return $this->plan($project);
    }

    #[Route('', name: 'api_stages_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new DeleteStage($id));

        return $this->plan($project);
    }

    #[Route('/start', name: 'api_stages_start', methods: ['POST'])]
    public function start(int $id, #[MapRequestPayload] StartStageInput $input): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new StartStage($id, new \DateTimeImmutable($input->actualStart)));

        return $this->plan($project);
    }

    #[Route('/lines', name: 'api_stages_lines_add', methods: ['POST'])]
    public function addLine(int $id, #[MapRequestPayload] LineInput $input): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new AddLine($id, $input->categoryId, $input->description, $input->unit, $input->quantity, $input->unitPrice));

        return $this->plan($project);
    }

    #[Route('/milestones', name: 'api_stages_milestones_add', methods: ['POST'])]
    public function addMilestone(int $id, #[MapRequestPayload] CreateMilestoneInput $input): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new AddMilestone($id, $input->name, Percent::toBasisPoints($input->weight), self::date($input->plannedDate)));

        return $this->plan($project);
    }

    private function project(int $stageId): int
    {
        $project = $this->known($this->parts->projectOfStage($stageId), 'stage_not_found');
        $this->guard(ProjectPermission::PLAN, $project);

        return $project;
    }
}
