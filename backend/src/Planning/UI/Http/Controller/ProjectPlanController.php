<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Controller;

use App\Planning\Application\Command\AddCategory;
use App\Planning\Application\Command\AddStage;
use App\Planning\Application\Command\ApproveBudget;
use App\Planning\Application\Command\ReorderStages;
use App\Planning\Application\Command\ReturnBudget;
use App\Planning\Application\Command\SetContingency;
use App\Planning\Application\Command\SubmitBudget;
use App\Planning\UI\Http\Input\CategoryInput;
use App\Planning\UI\Http\Input\ContingencyInput;
use App\Planning\UI\Http\Input\CreateStageInput;
use App\Planning\UI\Http\Input\ReorderInput;
use App\Planning\UI\Http\Input\ReturnBudgetInput;
use App\Planning\UI\Http\Output\PlanOutput;
use App\Shared\Application\Security\Actor;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/projects/{id}', requirements: ['id' => '\d+'])]
#[OA\Tag(name: 'Plan')]
#[OA\Response(response: 200, description: 'The refreshed plan', content: new Model(type: PlanOutput::class))]
#[OA\Response(response: 404, description: 'project_not_found (also when not a member)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class ProjectPlanController extends PlanEndpoint
{
    /** Any member; Team Leads get it without money. */
    #[Route('/plan', name: 'api_plan_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $this->guard(ProjectPermission::VIEW, $id);

        return $this->plan($id);
    }

    /** PM or Admin, also after approval. */
    #[Route('/categories', name: 'api_plan_categories_add', methods: ['POST'])]
    #[OA\Response(response: 422, description: 'validation_failed (name taken)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function addCategory(int $id, #[MapRequestPayload] CategoryInput $input): JsonResponse
    {
        $this->guard(ProjectPermission::PLAN, $id);
        $this->bus->dispatch(new AddCategory($id, $input->name));

        return $this->plan($id);
    }

    #[Route('/stages', name: 'api_plan_stages_add', methods: ['POST'])]
    #[OA\Response(response: 409, description: 'budget_locked', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function addStage(int $id, #[MapRequestPayload] CreateStageInput $input): JsonResponse
    {
        $this->guard(ProjectPermission::PLAN, $id);
        $this->bus->dispatch(new AddStage($id, $input->name, self::date($input->plannedStart), self::date($input->plannedEnd)));

        return $this->plan($id);
    }

    #[Route('/stages/order', name: 'api_plan_stages_order', methods: ['PUT'])]
    #[OA\Response(response: 422, description: 'validation_failed: ids must be every stage once', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function reorder(int $id, #[MapRequestPayload] ReorderInput $input): JsonResponse
    {
        $this->guard(ProjectPermission::PLAN, $id);
        $this->bus->dispatch(new ReorderStages($id, $input->ids));

        return $this->plan($id);
    }

    #[Route('/budget/contingency', name: 'api_plan_contingency', methods: ['PUT'])]
    public function contingency(int $id, #[MapRequestPayload] ContingencyInput $input): JsonResponse
    {
        $this->guard(ProjectPermission::PLAN, $id);
        $this->bus->dispatch(new SetContingency($id, $input->contingency));

        return $this->plan($id);
    }

    #[Route('/budget/submit', name: 'api_plan_submit', methods: ['POST'])]
    #[OA\Response(response: 422, description: 'budget_incomplete, with `issues`', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function submit(int $id, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard(ProjectPermission::PLAN, $id);
        $this->bus->dispatch(new SubmitBudget($id, $actor->getId()));

        return $this->plan($id);
    }

    /** Admin only: back to the PM with what to change. */
    #[Route('/budget/return', name: 'api_plan_return', methods: ['POST'])]
    #[OA\Response(response: 409, description: 'budget_not_submitted', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function return(int $id, #[CurrentUser] Actor $actor, #[MapRequestPayload] ReturnBudgetInput $input): JsonResponse
    {
        $this->guard(ProjectPermission::ADMINISTER, $id);
        $this->bus->dispatch(new ReturnBudget($id, $actor->getId(), $input->comment));

        return $this->plan($id);
    }

    /** Admin only: locks the budget and activates the project. */
    #[Route('/budget/approve', name: 'api_plan_approve', methods: ['POST'])]
    public function approve(int $id, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard(ProjectPermission::ADMINISTER, $id);
        $this->bus->dispatch(new ApproveBudget($id, $actor->getId()));

        return $this->plan($id);
    }
}
