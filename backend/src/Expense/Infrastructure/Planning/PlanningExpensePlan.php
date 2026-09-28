<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Planning;

use App\Expense\Application\Port\ExpensePlan;
use App\Planning\Application\Query\PlanDirectory;

final readonly class PlanningExpensePlan implements ExpensePlan
{
    public function __construct(private PlanDirectory $plans)
    {
    }

    public function isApproved(int $projectId): bool
    {
        return $this->plans->isApproved($projectId);
    }

    public function stages(int $projectId): array
    {
        $stages = [];
        foreach ($this->plans->stages($projectId) as $stage) {
            $stages[$stage->id] = ['name' => $stage->name, 'completed' => 'COMPLETED' === $stage->status];
        }

        return $stages;
    }

    public function categories(int $projectId): array
    {
        $categories = [];
        foreach ($this->plans->categories($projectId) as $category) {
            $categories[$category->id] = $category->name;
        }

        return $categories;
    }
}
