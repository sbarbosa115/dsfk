<?php

declare(strict_types=1);

namespace App\Planning\Domain\Repository;

use App\Planning\Domain\Model\Budget;
use App\Planning\Domain\Model\BudgetLine;
use App\Planning\Domain\Model\Category;
use App\Planning\Domain\Model\Milestone;
use App\Planning\Domain\Model\Stage;
use App\Shared\Domain\Error\NotFound;

interface PlanRepository
{
    /** The project's budget; created (as a draft) the first time a plan is written. */
    public function budgetFor(int $projectId): Budget;

    /** The budget if one was ever written (reads must not create it). */
    public function findBudget(int $projectId): ?Budget;

    /**
     * @return list<Stage> in their order
     */
    public function stagesOf(int $projectId): array;

    /**
     * @return list<Category> by name
     */
    public function categoriesOf(int $projectId): array;

    /**
     * @throws NotFound stage_not_found
     */
    public function stage(int $id): Stage;

    /**
     * @throws NotFound category_not_found
     */
    public function category(int $id): Category;

    /**
     * @throws NotFound line_not_found
     */
    public function line(int $id): BudgetLine;

    /**
     * @throws NotFound milestone_not_found
     */
    public function milestone(int $id): Milestone;

    public function addStage(Stage $stage): void;

    public function removeStage(Stage $stage): void;

    public function addCategory(Category $category): void;

    public function removeCategory(Category $category): void;

    /** Whether a budget line of the plan uses the category. */
    public function categoryHasLines(Category $category): bool;
}
