<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Facts;

use App\Expense\Application\Query\ExpenseDirectory;
use App\Finance\Application\Query\FinanceQueries;
use App\Identity\Application\Query\UserDirectory;
use App\Notification\Application\Port\NotificationFacts;
use App\Planning\Application\Query\PlanDirectory;
use App\Project\Application\Query\ProjectDirectory;
use App\Settings\Application\Query\SettingsQueries;

final readonly class ContextFacts implements NotificationFacts
{
    public function __construct(
        private ProjectDirectory $projects,
        private UserDirectory $users,
        private PlanDirectory $plans,
        private ExpenseDirectory $expenses,
        private FinanceQueries $finance,
        private SettingsQueries $settings,
    ) {
    }

    public function project(int $projectId): array
    {
        $info = $this->projects->info($projectId);

        return ['id' => $info->id, 'name' => $info->name, 'currency' => $info->currency];
    }

    public function personName(int $userId): string
    {
        return $this->users->view($userId)->fullName ?? '—';
    }

    public function expense(int $expenseId): ?array
    {
        $e = $this->expenses->facts($expenseId);
        if (null === $e) {
            return null;
        }
        $stages = array_column(array_map(static fn ($s): array => ['id' => $s->id, 'name' => $s->name], $this->plans->stages($e['projectId'])), 'name', 'id');
        $categories = array_column(array_map(static fn ($c): array => ['id' => $c->id, 'name' => $c->name], $this->plans->categories($e['projectId'])), 'name', 'id');

        return $e + [
            'stage' => $stages[$e['stageId']] ?? '—',
            'category' => $categories[$e['categoryId']] ?? '—',
            'paidBy' => $this->personName($e['paidById']),
        ];
    }

    public function budgetUse(int $projectId, int $stageId, int $categoryId): array
    {
        $spending = $this->expenses->spending($projectId);
        $stageBudget = 0;
        foreach ($this->plans->stages($projectId) as $stage) {
            if ($stage->id === $stageId) {
                $stageBudget = $stage->budget;
            }
        }
        $categoryBudget = 0;
        foreach ($this->plans->categories($projectId) as $category) {
            if ($category->id === $categoryId) {
                $categoryBudget = $category->budget;
            }
        }

        return [
            'stage' => ['budget' => $stageBudget, 'spent' => array_sum($spending[$stageId] ?? [])],
            'category' => ['budget' => $categoryBudget, 'spent' => array_sum(array_map(static fn (array $byCategory): int => $byCategory[$categoryId] ?? 0, $spending))],
        ];
    }

    public function budgetPercents(): array
    {
        return $this->settings->current()->budgetWarningPercents;
    }

    public function pettyCashLowPercent(): int
    {
        return $this->settings->current()->pettyCashLowBalancePercent;
    }

    public function pettyCash(int $projectId): array
    {
        return ['balance' => $this->finance->fundingTotals($projectId)['pettyCash'], 'lastTopUp' => $this->finance->lastPettyCashTopUp($projectId)];
    }

    public function cycle(int $cycleId): ?array
    {
        return $this->finance->cycleFacts($cycleId);
    }

    public function activeProjects(): array
    {
        return array_map(static fn ($p): int => $p->id, $this->projects->active());
    }

    public function digest(int $projectId, \DateTimeImmutable $today): array
    {
        return [
            'overdue' => $this->plans->overdueMilestones($projectId, $today),
            'pending' => $this->expenses->pendingCount($projectId),
            'toReimburse' => $this->expenses->toReimburseCount($projectId),
            'unsigned' => $this->finance->unsignedCycles($projectId),
        ];
    }
}
