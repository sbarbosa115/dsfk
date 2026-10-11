<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

final readonly class BudgetOutput
{
    /**
     * @param list<CategoryTotalOutput> $byCategory
     * @param list<BudgetEventOutput>   $events
     * @param list<AmendmentOutput>     $amendments
     */
    public function __construct(
        public string $contingency,
        /** Sum of the approved stages (contingency excluded) */
        public string $stagesTotal,
        public string $total,
        public ?\DateTimeImmutable $approvedAt,
        public array $byCategory,
        public array $events,
        /** Additions to the approved budget, oldest first. */
        public array $amendments,
    ) {
    }
}
