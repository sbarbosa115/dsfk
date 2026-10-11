<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Presenter;

use App\Planning\Application\Port\People;
use App\Planning\Application\Port\ProjectCatalog;
use App\Planning\Domain\Model\Budget;
use App\Planning\Domain\Model\BudgetAmendment;
use App\Planning\Domain\Model\BudgetLine;
use App\Planning\Domain\Model\BudgetStatus;
use App\Planning\Domain\Model\Milestone;
use App\Planning\Domain\Model\Stage;
use App\Planning\Domain\Repository\PlanRepository;
use App\Planning\Domain\Service\PlanCompleteness;
use App\Planning\Domain\Service\Progress;
use App\Planning\UI\Http\Output\AmendmentOutput;
use App\Planning\UI\Http\Output\BudgetEventOutput;
use App\Planning\UI\Http\Output\BudgetOutput;
use App\Planning\UI\Http\Output\CategoryOutput;
use App\Planning\UI\Http\Output\CategoryTotalOutput;
use App\Planning\UI\Http\Output\LineOutput;
use App\Planning\UI\Http\Output\MilestoneOutput;
use App\Planning\UI\Http\Output\PersonOutput;
use App\Planning\UI\Http\Output\PlanIssueOutput;
use App\Planning\UI\Http\Output\PlanOutput;
use App\Planning\UI\Http\Output\PlanPermissionsOutput;
use App\Planning\UI\Http\Output\PlanProjectOutput;
use App\Planning\UI\Http\Output\StageOutput;
use App\Shared\Domain\Money\MinorUnits;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Psr\Clock\ClockInterface;

/** The plan as the signed-in user may see it: Team Leads get no money figures. */
final readonly class PlanPresenter
{
    public function __construct(
        private PlanRepository $plans,
        private ProjectCatalog $projects,
        private People $people,
        private ProjectGuard $guard,
        private ClockInterface $clock,
    ) {
    }

    public function present(int $projectId): PlanOutput
    {
        $project = $this->projects->describe($projectId);
        $currency = $project['currency'];
        $budget = $this->plans->findBudget($projectId) ?? new Budget($projectId);
        $stages = $this->plans->stagesOf($projectId);
        $financials = $this->guard->allows(ProjectPermission::VIEW_FINANCIALS, $projectId);
        $canPlan = $this->guard->allows(ProjectPermission::PLAN, $projectId);
        $admin = $this->guard->allows(ProjectPermission::ADMINISTER, $projectId);
        $money = static fn (int $minor): string => MinorUnits::toMajor($minor, $currency);
        $names = $this->people->names(self::peopleIn($budget, $stages));
        $person = static fn (int $id): PersonOutput => new PersonOutput($id, $names[$id] ?? '—');
        $today = $this->clock->now();
        // A stage waiting for the Admin (in an open amendment) is shown, but counts in no figure yet.
        $approved = array_values(array_filter($stages, static fn (Stage $s): bool => !$s->isPendingApproval()));
        $open = $budget->isApproved() ? $budget->openAmendment() : null;
        $editable = static fn (Stage $s): bool => $canPlan && (null === $s->getAmendment() ? $budget->isEditable() : $s->getAmendment()->isEditable());

        $stageOutputs = array_map(static fn (Stage $s): StageOutput => new StageOutput(
            (int) $s->getId(),
            $s->getName(),
            $s->getPosition(),
            $s->getStatus()->value,
            $s->getPlannedStart()?->format('Y-m-d'),
            $s->getPlannedEnd()?->format('Y-m-d'),
            $s->getActualStart()?->format('Y-m-d'),
            $s->getActualEnd()?->format('Y-m-d'),
            $s->progress(),
            $s->milestoneWeightTotal(),
            array_map(static fn (Milestone $m): MilestoneOutput => new MilestoneOutput(
                (int) $m->getId(),
                $m->getName(),
                $m->getWeight(),
                $m->getPlannedDate()?->format('Y-m-d'),
                $m->getCompletedAt()?->format('Y-m-d'),
                null === $m->getCompletedById() ? null : $person($m->getCompletedById()),
                $m->getCompletionNotes(),
                $m->isOverdue($today),
            ), $s->getMilestones()),
            $financials ? $money($s->budgetTotal()) : null,
            $financials && !$s->isPendingApproval() ? Progress::stageWeight($s, $approved) : null,
            $financials ? array_map(static fn (BudgetLine $l): LineOutput => new LineOutput(
                (int) $l->getId(),
                (int) $l->getCategory()->getId(),
                $l->getDescription(),
                $l->getUnit(),
                $l->getQuantity(),
                $money($l->getUnitPrice()),
                $money($l->getTotal()),
            ), $s->getLines()) : null,
            $s->getAmendment()?->getNumber(),
            !$s->isPendingApproval(),
            $editable($s),
        ), $stages);

        $categories = $this->plans->categoriesOf($projectId);
        $stagesTotal = Progress::budget($approved);

        return new PlanOutput(
            new PlanProjectOutput($projectId, $project['name'], $currency, $project['status']),
            new PlanPermissionsOutput(
                $financials,
                $canPlan && $budget->isEditable(),
                $canPlan,
                $canPlan && ($budget->isEditable() || (null !== $open && $open->isEditable())),
                $admin && (BudgetStatus::Submitted === $budget->getStatus() || BudgetStatus::Submitted === $open?->getStatus()),
                $canPlan && $budget->isApproved(),
                $admin && $budget->isApproved(),
                $canPlan && ($budget->isEditable() || ($budget->isApproved() && (null === $open || $open->isEditable()))),
            ),
            $budget->getStatus()->value,
            Progress::ofProject($approved),
            $stageOutputs,
            array_map(static fn ($c): CategoryOutput => new CategoryOutput((int) $c->getId(), $c->getName()), $categories),
            $financials ? new BudgetOutput(
                $money($budget->getContingency()),
                $money($stagesTotal),
                $money($stagesTotal + $budget->getContingency()),
                $budget->getApprovedAt(),
                array_map(static fn ($c): CategoryTotalOutput => new CategoryTotalOutput((int) $c->getId(), $money(self::categoryTotal((int) $c->getId(), $approved))), $categories),
                array_map(static fn ($e): BudgetEventOutput => new BudgetEventOutput($e->getStatus()->value, $person($e->getUserId()), $e->getComment(), $e->getCreatedAt(), $e->getAmendment()?->getNumber()), $budget->getEvents()),
                array_map(static fn (BudgetAmendment $a): AmendmentOutput => self::amendment($a, $stages, $money), $budget->getAmendments()),
            ) : null,
            $financials ? ($budget->isEditable() || (null !== $open && $open->isEditable()) ? array_map(
                static fn (array $i): PlanIssueOutput => new PlanIssueOutput($i['code'], $i['stageId'] ?? null),
                PlanCompleteness::issues($budget->underReview($stages)),
            ) : []) : null,
        );
    }

    /**
     * @param list<Stage>           $stages
     * @param callable(int): string $money
     */
    private static function amendment(BudgetAmendment $amendment, array $stages, callable $money): AmendmentOutput
    {
        $own = array_values(array_filter($stages, static fn (Stage $s): bool => $s->getAmendment() === $amendment));

        return new AmendmentOutput(
            $amendment->getNumber(),
            $amendment->getStatus()->value,
            array_map(static fn (Stage $s): string => $s->getName(), $own),
            $money(Progress::budget($own)),
            $amendment->getApprovedAt(),
        );
    }

    /**
     * @param list<Stage> $stages
     */
    private static function categoryTotal(int $categoryId, array $stages): int
    {
        $total = 0;
        foreach ($stages as $stage) {
            foreach ($stage->getLines() as $line) {
                if ($line->getCategory()->getId() === $categoryId) {
                    $total += $line->getTotal();
                }
            }
        }

        return $total;
    }

    /**
     * @param list<Stage> $stages
     *
     * @return list<int>
     */
    private static function peopleIn(Budget $budget, array $stages): array
    {
        $ids = array_map(static fn ($e): int => $e->getUserId(), $budget->getEvents());
        foreach ($stages as $stage) {
            foreach ($stage->getMilestones() as $milestone) {
                if (null !== $milestone->getCompletedById()) {
                    $ids[] = $milestone->getCompletedById();
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
