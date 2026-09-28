<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Controller;

use App\Finance\Application\Command\Allocation;
use App\Finance\Application\Command\DrawContingency;
use App\Finance\Application\Command\RecordDeposit;
use App\Finance\Application\Query\FinanceQueries;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Model\PaymentMethod;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Finance\UI\Http\Input\AllocationInput;
use App\Finance\UI\Http\Input\CompleteStageInput;
use App\Finance\UI\Http\Input\DepositInput;
use App\Finance\UI\Http\Input\DrawInput;
use App\Finance\UI\Http\Output\FinanceOutput;
use App\Finance\UI\Http\Output\MovementOutput;
use App\Finance\UI\Http\Output\MovementPageOutput;
use App\Finance\UI\Http\Presenter\FinancePresenter;
use App\Planning\Application\Command\CompleteStage;
use App\Planning\Application\Query\PlanQueries;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\NewId;
use App\Shared\Application\Security\Actor;
use App\Shared\Domain\Error\NotFound;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Finance')]
#[OA\Response(response: 404, description: 'project_not_found (also outside the project), stage_not_found', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 403, description: 'forbidden: Team Leads see no money; only Admins move it', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class FinanceController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly ProjectGuard $guard,
        private readonly FinancePresenter $presenter,
        private readonly FinanceQueries $queries,
        private readonly LedgerRepository $ledger,
    ) {
    }

    /** Balances per stage, petty cash and contingency next to the budget: for the Admins and the PM. */
    #[Route('/api/projects/{id}/finance', name: 'api_finance_summary', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The funding summary', content: new Model(type: FinanceOutput::class))]
    public function summary(int $id): JsonResponse
    {
        $this->guard->require(ProjectPermission::VIEW_FINANCIALS, $id);

        return $this->json($this->presenter->summary($id));
    }

    /** Deposits, draws and carry-overs (voided ones included), newest first. `q` searches reference and note. */
    #[Route('/api/projects/{id}/movements', name: 'api_finance_movements', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'One page of movements', content: new Model(type: MovementPageOutput::class))]
    public function movements(
        int $id,
        #[MapQueryParameter] ?string $q = null,
        #[MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[MapQueryParameter(options: ['min_range' => 1, 'max_range' => 100])] int $perPage = 50,
    ): JsonResponse {
        $this->guard->require(ProjectPermission::VIEW_FINANCIALS, $id);
        $result = $this->queries->fundingPage($id, $q, $page, $perPage);

        return $this->json(new MovementPageOutput($this->presenter->movements($id, $result->items), $result->total, $page, $perPage));
    }

    #[Route('/api/projects/{id}/deposits', name: 'api_finance_deposit', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The deposit', content: new Model(type: MovementOutput::class))]
    #[OA\Response(response: 409, description: 'budget_not_approved', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed (fields such as allocations[1].stageId)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function deposit(int $id, #[MapRequestPayload] DepositInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::ADMINISTER, $id);
        $movement = $this->bus->dispatch(new RecordDeposit(
            $id,
            $actor->getId(),
            new \DateTimeImmutable($input->date),
            $input->method ?? PaymentMethod::Other,
            $input->reference,
            $input->note,
            array_map(static fn (AllocationInput $a): Allocation => new Allocation($a->destination ?? LedgerAccount::Stage, $a->amount, $a->stageId, $a->categoryId), $input->allocations),
        ));

        return $this->created($movement);
    }

    #[Route('/api/projects/{id}/contingency/draws', name: 'api_finance_draw', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 201, description: 'The draw', content: new Model(type: MovementOutput::class))]
    #[OA\Response(response: 409, description: 'budget_not_approved', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed (amount above the contingency balance, stage completed)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function draw(int $id, #[MapRequestPayload] DrawInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::ADMINISTER, $id);
        $movement = $this->bus->dispatch(new DrawContingency($id, $actor->getId(), $input->stageId, $input->amount, new \DateTimeImmutable($input->date), $input->reason));

        return $this->created($movement);
    }

    /** The Admin closes a stage whose milestones are met; what is left of its money moves to the next open stage. */
    #[Route('/api/stages/{id}/complete', name: 'api_stages_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The refreshed funding summary', content: new Model(type: FinanceOutput::class))]
    #[OA\Response(response: 409, description: 'budget_not_approved, stage_not_in_progress, stage_milestones_pending', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function completeStage(int $id, #[MapRequestPayload] CompleteStageInput $input, #[CurrentUser] Actor $actor, PlanQueries $plan): JsonResponse
    {
        $project = $plan->projectOfStage($id) ?? throw new NotFound('stage_not_found');
        $this->guard->require(ProjectPermission::ADMINISTER, $project);
        $this->bus->dispatch(new CompleteStage($id, new \DateTimeImmutable($input->actualEnd), $actor->getId()));

        return $this->json($this->presenter->summary($project));
    }

    private function created(mixed $id): JsonResponse
    {
        \assert($id instanceof NewId);

        return $this->json($this->presenter->movement($this->ledger->movement($id->value())), 201);
    }
}
