<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

final readonly class PettyCashOutput
{
    /**
     * @param list<CycleOutput> $history closed cycles, newest first
     */
    public function __construct(
        public string $currency,
        /** Major units. */
        public string $balance,
        public CycleOutput $current,
        public array $history,
        /** Closed cycles waiting for an Admin's sign-off. */
        public int $unsignedCount,
        public PettyCashPermissionsOutput $permissions,
    ) {
    }
}
