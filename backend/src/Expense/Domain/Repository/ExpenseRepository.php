<?php

declare(strict_types=1);

namespace App\Expense\Domain\Repository;

use App\Expense\Domain\Model\Expense;
use App\Expense\Domain\Model\Reimbursement;
use App\Shared\Domain\Error\NotFound;

interface ExpenseRepository
{
    /** Written at once (inside the command's transaction): its events and Finance's movement need its id. */
    public function add(Expense $expense): void;

    public function addReimbursement(Reimbursement $reimbursement): void;

    /**
     * @throws NotFound expense_not_found
     */
    public function get(int $id): Expense;

    /**
     * @param list<int> $ids
     *
     * @return list<Expense> the ones that exist
     */
    public function many(array $ids): array;
}
