<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

use OpenApi\Attributes as OA;

final readonly class LedgerEntryOutput
{
    public function __construct(
        #[OA\Property(enum: ['STAGE', 'PETTY_CASH', 'CONTINGENCY'])]
        public string $account,
        public ?int $stageId,
        public ?string $stageName,
        public ?int $categoryId,
        public ?string $categoryName,
        /** Major units, signed: negative when the account gives money. */
        public string $amount,
    ) {
    }
}
