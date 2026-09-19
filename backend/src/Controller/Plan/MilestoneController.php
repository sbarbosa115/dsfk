<?php

namespace App\Controller\Plan;

use App\Dto\CompleteMilestoneInput;
use App\Dto\MilestoneInput;
use App\Entity\Milestone;
use App\Entity\User;
use App\Security\ProjectVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/milestones/{id}', requirements: ['id' => '\d+'])]
class MilestoneController extends AbstractPlanController
{
    #[Route('', methods: ['PATCH'])]
    public function update(Milestone $milestone, #[MapRequestPayload] MilestoneInput $input): JsonResponse
    {
        $project = $milestone->getStage()->getProject();
        $this->denyUnlessEditable($project);

        if (null !== $input->name) {
            $milestone->setName($input->name);
        }
        if (null !== $input->weight) {
            $milestone->setWeight($input->weightInBasisPoints());
            StageController::assertWeightWithinStage($milestone->getStage());
        }
        if (null !== $input->plannedDate) {
            $milestone->setPlannedDate($input->plannedDate);
        }

        return $this->flushAndPlan($project);
    }

    #[Route('', methods: ['DELETE'])]
    public function delete(Milestone $milestone): JsonResponse
    {
        $project = $milestone->getStage()->getProject();
        $this->denyUnlessEditable($project);
        $milestone->getStage()->removeMilestone($milestone);

        return $this->flushAndPlan($project);
    }

    #[Route('/complete', methods: ['POST'])]
    public function complete(Milestone $milestone, #[MapRequestPayload] CompleteMilestoneInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $project = $milestone->getStage()->getProject();
        $this->denyUnlessTracking($project);
        $milestone->complete($input->completedAt, $user, $input->notes);

        return $this->flushAndPlan($project);
    }

    /** Undoing a completion changes reported progress, so only the Admin may do it. */
    #[Route('/reopen', methods: ['POST'])]
    public function reopen(Milestone $milestone): JsonResponse
    {
        $project = $milestone->getStage()->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::ADMINISTER, $project);
        $milestone->reopen();

        return $this->flushAndPlan($project);
    }
}
