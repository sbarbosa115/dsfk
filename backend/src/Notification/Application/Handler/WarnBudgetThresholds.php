<?php

declare(strict_types=1);

namespace App\Notification\Application\Handler;

use App\Expense\Domain\Event\SpendingRecorded;
use App\Notification\Application\Port\NotificationFacts;
use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipients;
use App\Shared\Application\Bus\EventHandler;
use App\Shared\Domain\Money\MinorUnits;

/**
 * An expense started counting against the budget: when its stage's or its category's spending crosses one of the
 * warning percentages (e.g. 80 and 100), the Admins and the PM hear it, once per crossing (the highest one).
 */
final readonly class WarnBudgetThresholds implements EventHandler
{
    public function __construct(private Recipients $recipients, private NotificationFacts $facts, private Outbox $outbox)
    {
    }

    public function __invoke(SpendingRecorded $event): void
    {
        $expense = $this->facts->expense($event->expenseId);
        if (null === $expense) {
            return;
        }
        $use = $this->facts->budgetUse($event->projectId, $expense['stageId'], $expense['categoryId']);
        $this->check($event->projectId, 'stage', $expense['stage'], $use['stage']['budget'], $use['stage']['spent'], $expense['amount']);
        $this->check($event->projectId, 'category', $expense['category'], $use['category']['budget'], $use['category']['spent'], $expense['amount']);
    }

    /**
     * @param 'stage'|'category' $kind
     * @param int                $after spending with the expense, minor units
     */
    private function check(int $projectId, string $kind, string $name, int $budget, int $after, int $amount): void
    {
        if ($budget <= 0) {
            return;
        }
        $before = $after - $amount;
        $percents = $this->facts->budgetPercents();
        rsort($percents);
        foreach ($percents as $percent) {
            if ($before * 100 < $budget * $percent && $after * 100 >= $budget * $percent) {
                $project = $this->facts->project($projectId);
                $this->outbox->send([...$this->recipients->admins(), $this->recipients->projectManager($projectId)], 'budget_threshold', ['percent' => $percent, 'project' => $project['name']], [
                    'project' => $project,
                    'kind' => $kind,
                    'name' => $name,
                    'percent' => $percent,
                    'spent' => MinorUnits::format($after, $project['currency']),
                    'budget' => MinorUnits::format($budget, $project['currency']),
                    'executed' => str_replace('.', ',', (string) round($after * 100 / $budget, 1)),
                ]);

                return;
            }
        }
    }
}
