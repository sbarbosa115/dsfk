<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class ExpenseOutput
{
    /**
     * @param list<ExpenseFileOutput>       $attachments receipts
     * @param list<ExpenseEventOutput>|null $events      the history; null in lists
     */
    public function __construct(
        public int $id,
        #[OA\Property(format: 'date')]
        public string $date,
        /** Major units. */
        public string $amount,
        public string $description,
        public ?string $supplier,
        public ?string $invoiceNumber,
        public ExpenseRefOutput $stage,
        public ExpenseRefOutput $category,
        #[OA\Property(enum: ['STAGE', 'PETTY_CASH', 'OUT_OF_POCKET'])]
        public string $paidFrom,
        public ExpenseRefOutput $paidBy,
        #[OA\Property(enum: ['SUBMITTED', 'PM_APPROVED', 'APPROVED', 'REJECTED', 'REIMBURSED', 'VOIDED'])]
        public string $status,
        public ?string $rejectionReason,
        public ?ExpenseReimbursementOutput $reimbursement,
        #[OA\Property(format: 'date-time')]
        public string $createdAt,
        public array $attachments,
        public ExpensePermissionsOutput $permissions,
        public ?array $events,
    ) {
    }
}
