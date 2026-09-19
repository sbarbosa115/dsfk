<?php

namespace App\Controller\Plan;

use App\Dto\BudgetLineInput;
use App\Dto\MilestoneInput;
use App\Dto\StageInput;
use App\Dto\StartStageInput;
use App\Entity\BudgetLine;
use App\Entity\Category;
use App\Entity\Milestone;
use App\Entity\Stage;
use App\Exception\ApiProblem;
use App\Security\ProjectVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stages/{id}', requirements: ['id' => '\d+'])]
class StageController extends AbstractPlanController
{
    /** The name can always be fixed; planned dates are part of the locked baseline. */
    #[Route('', methods: ['PATCH'])]
    public function update(Stage $stage, #[MapRequestPayload] StageInput $input): JsonResponse
    {
        $project = $stage->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::PLAN, $project);

        if (null !== $input->name) {
            $stage->setName($input->name);
        }
        if (null !== $input->plannedStart || null !== $input->plannedEnd) {
            $project->getBudget()->assertEditable();
            $stage->setPlannedStart($input->plannedStart ?? $stage->getPlannedStart());
            $stage->setPlannedEnd($input->plannedEnd ?? $stage->getPlannedEnd());
            if ($stage->getPlannedStart() && $stage->getPlannedEnd() && $stage->getPlannedEnd() < $stage->getPlannedStart()) {
                throw ApiProblem::field('plannedEnd', 'La fecha de fin debe ser posterior al inicio.');
            }
        }

        return $this->flushAndPlan($project);
    }

    #[Route('', methods: ['DELETE'])]
    public function delete(Stage $stage): JsonResponse
    {
        $project = $stage->getProject();
        $this->denyUnlessEditable($project);
        $project->removeStage($stage);

        return $this->flushAndPlan($project);
    }

    #[Route('/start', methods: ['POST'])]
    public function start(Stage $stage, #[MapRequestPayload] StartStageInput $input): JsonResponse
    {
        $this->denyUnlessTracking($stage->getProject());
        $stage->start($input->actualStart);

        return $this->flushAndPlan($stage->getProject());
    }

    #[Route('/lines', methods: ['POST'])]
    public function addLine(Stage $stage, #[MapRequestPayload] BudgetLineInput $input): JsonResponse
    {
        $project = $stage->getProject();
        $this->denyUnlessEditable($project);

        $position = 0;
        foreach ($stage->getBudgetLines() as $line) {
            $position = max($position, $line->getPosition() + 1);
        }
        $stage->addBudgetLine(new BudgetLine(
            $stage,
            $this->category($stage, $input->categoryId),
            $input->description,
            $input->unit,
            $input->quantity,
            $this->toMinor($input->unitPrice, $project, 'unitPrice'),
            $position,
        ));

        return $this->flushAndPlan($project);
    }

    #[Route('/milestones', methods: ['POST'])]
    public function addMilestone(Stage $stage, #[MapRequestPayload(validationGroups: ['Default', 'create'])] MilestoneInput $input): JsonResponse
    {
        $project = $stage->getProject();
        $this->denyUnlessEditable($project);

        $position = 0;
        foreach ($stage->getMilestones() as $milestone) {
            $position = max($position, $milestone->getPosition() + 1);
        }
        $milestone = new Milestone($stage, $input->name, $input->weightInBasisPoints(), $position);
        $milestone->setPlannedDate($input->plannedDate);
        $stage->addMilestone($milestone);
        self::assertWeightWithinStage($stage);

        return $this->flushAndPlan($project);
    }

    public static function assertWeightWithinStage(Stage $stage): void
    {
        if ($stage->getMilestoneWeightTotal() > 10000) {
            throw ApiProblem::field('weight', 'La suma de los pesos de la etapa no puede superar el 100%.');
        }
    }

    private function category(Stage $stage, int $categoryId): Category
    {
        $category = $this->em->find(Category::class, $categoryId);
        if (null === $category || $category->getProject() !== $stage->getProject()) {
            throw ApiProblem::field('categoryId', 'Categoría inválida.');
        }

        return $category;
    }
}
