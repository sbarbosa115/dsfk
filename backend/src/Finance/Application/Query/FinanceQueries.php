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

    /**
     * Deposited (all accounts), available (all accounts) and in the caja menor, minor units, voids left out.
     *
     * @return array{deposited: int, available: int, pettyCash: int}
     */
    public function fundingTotals(int $projectId): array;

    /**
     * Deposits per month from `$from` on (voids left out).
     *
     * @return array<string, int> minor units by YYYY-MM
     */
    public function monthlyDeposits(int $projectId, \DateTimeImmutable $from): array;

    /** Closed caja menor cycles waiting for an Admin's sign-off. */
    public function unsignedCycles(int $projectId): int;

    /**
     * A closed cycle as a notification tells it. null: no such cycle.
     *
     * @return array{projectId: int, number: int, closingBalance: int, closedById: ?int, note: ?string}|null
     */
    public function cycleFacts(int $cycleId): ?array;

    /** The caja menor's last top-up (a deposit's part for it), minor units; 0 if it never had one. */
    public function lastPettyCashTopUp(int $projectId): int;

    /** Whether any ledger entry earmarks money for the category (so Planning keeps it). */
    public function usesCategory(int $categoryId): bool;
}
