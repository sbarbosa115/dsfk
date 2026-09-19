<?php

namespace App\Controller\Plan;

use App\Dto\CategoryInput;
use App\Dto\ContingencyInput;
use App\Dto\ReorderInput;
use App\Dto\ReturnBudgetInput;
use App\Dto\StageInput;
use App\Entity\Category;
use App\Entity\Project;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Exception\ApiProblem;
use App\Security\ProjectVoter;
use App\Service\Alerts;
use App\Service\PlanValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/projects/{id}', requirements: ['id' => '\d+'])]
class ProjectPlanController extends AbstractPlanController
{
    #[Route('/plan', methods: ['GET'])]
    #[IsGranted(ProjectVoter::VIEW, 'project')]
    public function show(Project $project): JsonResponse
    {
        return $this->plan($project);
    }

    /** Categories can be added even after approval (new costs show up as unbudgeted). */
    #[Route('/categories', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PLAN, 'project')]
    public function addCategory(Project $project, #[MapRequestPayload] CategoryInput $input): JsonResponse
    {
        $this->assertCategoryNameAvailable($project, $input->name);
        $project->addCategory(new Category($project, $input->name));

        return $this->flushAndPlan($project);
    }

    #[Route('/stages', methods: ['POST'])]
    public function addStage(Project $project, #[MapRequestPayload(validationGroups: ['Default', 'create'])] StageInput $input): JsonResponse
    {
        $this->denyUnlessEditable($project);

        $position = 0;
        foreach ($project->getStages() as $stage) {
            $position = max($position, $stage->getPosition() + 1);
        }
        $stage = new Stage($project, $input->name, $position);
        $stage->setPlannedStart($input->plannedStart);
        $stage->setPlannedEnd($input->plannedEnd);
        $project->addStage($stage);

        return $this->flushAndPlan($project);
    }

    #[Route('/stages/order', methods: ['PUT'])]
    public function reorderStages(Project $project, #[MapRequestPayload] ReorderInput $input): JsonResponse
    {
        $this->denyUnlessEditable($project);

        $stages = [];
        foreach ($project->getStages() as $stage) {
            $stages[$stage->getId()] = $stage;
        }
        $ids = $input->ids;
        sort($ids);
        $existing = array_keys($stages);
        sort($existing);
        if ($ids !== $existing) {
            throw ApiProblem::field('ids', 'La lista debe contener todas las etapas del proyecto.');
        }

        foreach ($input->ids as $position => $id) {
            $stages[$id]->setPosition($position);
        }

        return $this->flushAndPlan($project);
    }

    #[Route('/budget/contingency', methods: ['PUT'])]
    public function setContingency(Project $project, #[MapRequestPayload] ContingencyInput $input): JsonResponse
    {
        $this->denyUnlessEditable($project);
        $project->getBudget()->setContingency($this->toMinor($input->contingency, $project, 'contingency'));

        return $this->flushAndPlan($project);
    }

    #[Route('/budget/submit', methods: ['POST'])]
    public function submit(Project $project, #[CurrentUser] User $user, PlanValidator $validator, Alerts $alerts): JsonResponse
    {
        $this->denyUnlessEditable($project);
        $this->assertComplete($project, $validator);
        $project->getBudget()->submit($user);
        $alerts->budgetSubmitted($project->getBudget(), $user);

        return $this->flushAndPlan($project);
    }

    #[Route('/budget/return', methods: ['POST'])]
    #[IsGranted(ProjectVoter::ADMINISTER, 'project')]
    public function returnToDraft(Project $project, #[MapRequestPayload] ReturnBudgetInput $input, #[CurrentUser] User $user, Alerts $alerts): JsonResponse
    {
        $project->getBudget()->returnToDraft($user, trim($input->comment));
        $alerts->budgetReviewed($project->getBudget(), $user, trim($input->comment));

        return $this->flushAndPlan($project);
    }

    #[Route('/budget/approve', methods: ['POST'])]
    #[IsGranted(ProjectVoter::ADMINISTER, 'project')]
    public function approve(Project $project, #[CurrentUser] User $user, PlanValidator $validator, Alerts $alerts): JsonResponse
    {
        $this->assertComplete($project, $validator);
        $project->getBudget()->approve($user);
        $alerts->budgetReviewed($project->getBudget(), $user, null);
        if (ProjectStatus::Draft === $project->getStatus()) {
            $project->setStatus(ProjectStatus::Active);
        }

        return $this->flushAndPlan($project);
    }

    private function assertComplete(Project $project, PlanValidator $validator): void
    {
        $issues = $validator->issues($project);
        if ([] !== $issues) {
            throw new ApiProblem('budget_incomplete', ['issues' => $issues]);
        }
    }

    private function assertCategoryNameAvailable(Project $project, string $name): void
    {
        foreach ($project->getCategories() as $category) {
            if (mb_strtolower($category->getName()) === mb_strtolower(trim($name))) {
                throw ApiProblem::field('name', 'Ya existe una categoría con ese nombre.');
            }
        }
    }
}
