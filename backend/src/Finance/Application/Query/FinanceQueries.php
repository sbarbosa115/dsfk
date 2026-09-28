<?php

declare(strict_types=1);

namespace App\Finance\Application\Query;

use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\PettyCashCycle;
use App\Shared\Application\Query\Page;

interface FinanceQueries
{
    /** The project a movement belongs to, so a controller checks access first. null: no such movement. */
    public function projectOfMovement(int $movementId): ?int;

    /**
     * Funding movements (deposits, draws, carry-overs), newest first; `$search` looks in the reference and note.
     *
     * @return Page<FundMovement>
     */
    public function fundingPage(int $projectId, ?string $search, int $page, int $perPage): Page;

    public function projectOfCycle(int $cycleId): ?int;

    /**
     * @param list<int> $movementIds
     *
     * @return array<int, string> YYYY-MM-DD by movement id
     */
    public function movementDates(array $movementIds): array;

    /**
     * @return list<PettyCashCycle> newest first
     */
    public function cycles(int $projectId): array;

    /**
     * Movements of a cycle (voided ones included), oldest first.
     *
     * @return list<FundMovement>
     */
    public function cycleMovements(int $cycleId): array;

    /** Whether any ledger entry earmarks money for the category (so Planning keeps it). */
    public function usesCategory(int $categoryId): bool;
}
