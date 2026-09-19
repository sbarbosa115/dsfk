<?php

namespace App\Service;

use App\Entity\BudgetEvent;
use App\Entity\BudgetLine;
use App\Entity\Category;
use App\Entity\Milestone;
use App\Entity\Project;
use App\Entity\Stage;
use App\Security\ProjectVoter;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * JSON shape of a project's plan: stages, budget lines, milestones, totals and progress.
 * Amounts are decimal strings in major units; weights and progress are basis points (10000 = 100%).
 * Team Leads get the plan without any money figures.
 */
class PlanPresenter
{
    public function __construct(
        private readonly Security $security,
        private readonly PlanValidator $validator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Project $project): array
    {
        $financials = $this->security->isGranted(ProjectVoter::VIEW_FINANCIALS, $project);
        $canPlan = $this->security->isGranted(ProjectVoter::PLAN, $project);
        $canAdminister = $this->security->isGranted(ProjectVoter::ADMINISTER, $project);
        $budget = $project->getBudget();
        $currency = $project->getCurrency();
        $money = static fn (int $minor): string => MoneyConverter::toMajor($minor, $currency);

        $stagesTotal = 0;
        foreach ($project->getStages() as $stage) {
            $stagesTotal += $stage->getBudgetTotal();
        }

        $data = [
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'currency' => $currency,
                'status' => $project->getStatus()->value,
            ],
            'permissions' => [
                'viewFinancials' => $financials,
                'edit' => $canPlan && $budget->isEditable(),
                'manageCategories' => $canPlan,
                'submit' => $canPlan && $budget->isEditable(),
                'review' => $canAdminister && 'SUBMITTED' === $budget->getStatus()->value,
                'track' => $canPlan && $budget->isApproved(),
                'reopenMilestones' => $canAdminister && $budget->isApproved(),
            ],
            'budgetStatus' => $budget->getStatus()->value,
            'progress' => self::projectProgress($project, $stagesTotal),
            'stages' => array_map(fn (Stage $s) => $this->stage($s, $financials ? $money : null, $stagesTotal), $project->getStages()->toArray()),
            'categories' => array_map(static fn (Category $c) => ['id' => $c->getId(), 'name' => $c->getName()], $project->getCategories()->toArray()),
        ];

        if ($financials) {
            $data['budget'] = [
                'contingency' => $money($budget->getContingency()),
                'stagesTotal' => $money($stagesTotal),
                'total' => $money($stagesTotal + $budget->getContingency()),
                'approvedAt' => $budget->getApprovedAt()?->format(\DATE_ATOM),
                'byCategory' => $this->byCategory($project, $money),
                'events' => array_map(static fn (BudgetEvent $e) => [
                    'status' => $e->getStatus()->value,
                    'user' => ['id' => $e->getUser()->getId(), 'fullName' => $e->getUser()->getFullName()],
                    'comment' => $e->getComment(),
                    'createdAt' => $e->getCreatedAt()->format(\DATE_ATOM),
                ], $budget->getEvents()->toArray()),
            ];
            $data['issues'] = $budget->isEditable() ? $this->validator->issues($project) : [];
        }

        return $data;
    }

    /**
     * @param (\Closure(int): string)|null $money null hides every amount
     *
     * @return array<string, mixed>
     */
    private function stage(Stage $stage, ?\Closure $money, int $stagesTotal): array
    {
        $data = [
            'id' => $stage->getId(),
            'name' => $stage->getName(),
            'position' => $stage->getPosition(),
            'status' => $stage->getStatus()->value,
            'plannedStart' => $stage->getPlannedStart()?->format('Y-m-d'),
            'plannedEnd' => $stage->getPlannedEnd()?->format('Y-m-d'),
            'actualStart' => $stage->getActualStart()?->format('Y-m-d'),
            'actualEnd' => $stage->getActualEnd()?->format('Y-m-d'),
            'progress' => $stage->getProgress(),
            'milestoneWeightTotal' => $stage->getMilestoneWeightTotal(),
            'milestones' => array_map($this->milestone(...), $stage->getMilestones()->toArray()),
        ];

        if (null !== $money) {
            $data['budgetTotal'] = $money($stage->getBudgetTotal());
            $data['weight'] = $stagesTotal > 0 ? intdiv($stage->getBudgetTotal() * 10000 + intdiv($stagesTotal, 2), $stagesTotal) : 0;
            $data['lines'] = array_map(static fn (BudgetLine $l) => [
                'id' => $l->getId(),
                'categoryId' => $l->getCategory()->getId(),
                'description' => $l->getDescription(),
                'unit' => $l->getUnit(),
                'quantity' => rtrim(rtrim($l->getQuantity(), '0'), '.'),
                'unitPrice' => $money($l->getUnitPrice()),
                'total' => $money($l->getTotal()),
            ], $stage->getBudgetLines()->toArray());
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function milestone(Milestone $m): array
    {
        $today = new \DateTimeImmutable('today');

        return [
            'id' => $m->getId(),
            'name' => $m->getName(),
            'weight' => $m->getWeight(),
            'plannedDate' => $m->getPlannedDate()?->format('Y-m-d'),
            'completedAt' => $m->getCompletedAt()?->format('Y-m-d'),
            'completedBy' => $m->getCompletedBy() ? ['id' => $m->getCompletedBy()->getId(), 'fullName' => $m->getCompletedBy()->getFullName()] : null,
            'completionNotes' => $m->getCompletionNotes(),
            'overdue' => !$m->isCompleted() && null !== $m->getPlannedDate() && $m->getPlannedDate() < $today,
        ];
    }

    /**
     * Stage progress weighted by each stage's share of the budget. Falls back to a
     * plain average while the budget is still empty.
     */
    public static function projectProgress(Project $project, int $stagesTotal): int
    {
        $stages = $project->getStages();
        if ($stages->isEmpty()) {
            return 0;
        }
        if (0 === $stagesTotal) {
            $sum = 0;
            foreach ($stages as $stage) {
                $sum += $stage->getProgress();
            }

            return intdiv($sum, $stages->count());
        }

        $weighted = 0;
        foreach ($stages as $stage) {
            $weighted += $stage->getProgress() * $stage->getBudgetTotal();
        }

        return intdiv($weighted, $stagesTotal);
    }

    /**
     * @param \Closure(int): string $money
     *
     * @return list<array{categoryId: int, total: string}>
     */
    private function byCategory(Project $project, \Closure $money): array
    {
        $totals = [];
        foreach ($project->getStages() as $stage) {
            foreach ($stage->getBudgetLines() as $line) {
                $id = $line->getCategory()->getId();
                $totals[$id] = ($totals[$id] ?? 0) + $line->getTotal();
            }
        }

        $result = [];
        foreach ($project->getCategories() as $category) {
            $result[] = ['categoryId' => $category->getId(), 'total' => $money($totals[$category->getId()] ?? 0)];
        }

        return $result;
    }
}
