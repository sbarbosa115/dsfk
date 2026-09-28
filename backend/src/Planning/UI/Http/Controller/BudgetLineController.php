<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Controller;

use App\Planning\Application\Command\ChangeLine;
use App\Planning\Application\Command\DeleteLine;
use App\Planning\UI\Http\Input\LineInput;
use App\Planning\UI\Http\Output\PlanOutput;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/budget-lines/{id}', requirements: ['id' => '\d+'])]
#[OA\Tag(name: 'Plan')]
#[OA\Response(response: 200, description: 'The refreshed plan', content: new Model(type: PlanOutput::class))]
#[OA\Response(response: 409, description: 'budget_locked', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class BudgetLineController extends PlanEndpoint
{
    #[Route('', name: 'api_lines_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload] LineInput $input): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new ChangeLine($id, $input->categoryId, $input->description, $input->unit, $input->quantity, $input->unitPrice));

        return $this->plan($project);
    }

    #[Route('', name: 'api_lines_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new DeleteLine($id));

        return $this->plan($project);
    }

    private function project(int $lineId): int
    {
        $project = $this->known($this->parts->projectOfLine($lineId), 'line_not_found');
        $this->guard(ProjectPermission::PLAN, $project);

        return $project;
    }
}
