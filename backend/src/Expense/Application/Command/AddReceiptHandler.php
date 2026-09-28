<?php

declare(strict_types=1);

namespace App\Expense\Application\Command;

use App\Expense\Application\Port\ReceiptStore;
use App\Expense\Domain\Model\ExpenseStatus;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\NotAllowed;

final readonly class AddReceiptHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses, private ReceiptStore $receipts)
    {
    }

    public function __invoke(AddReceipt $c): void
    {
        $expense = $this->expenses->get($c->expenseId);
        $owner = $expense->getPaidById() === $c->actorId && $expense->isCorrectable();
        $manager = $c->manager && ExpenseStatus::Voided !== $expense->getStatus();
        if (!$owner && !$manager) {
            throw new NotAllowed('forbidden');
        }
        $this->receipts->attach($expense->getProjectId(), $c->expenseId, $c->path, $c->originalName, $c->size, $c->actorId);
    }
}
