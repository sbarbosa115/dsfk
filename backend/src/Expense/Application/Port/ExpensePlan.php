<?php

declare(strict_types=1);

namespace App\Expense\Application\Port;

/** The Planning context: whether spending is open, and the project's stages and categories by id. */
interface ExpensePlan
{
    public function isApproved(int $projectId): bool;

    /**
     * @return array<int, array{name: string, completed: bool}> by stage id
     */
    public function stages(int $projectId): array;

    /**
     * @return array<int, string> category name by id
     */
    public function categories(int $projectId): array;
}
