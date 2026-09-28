<?php

declare(strict_types=1);

namespace App\Finance\Domain\Repository;

use App\Finance\Domain\Model\Balances;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\PettyCashCycle;
use App\Shared\Domain\Error\NotFound;

interface LedgerRepository
{
    public function add(FundMovement $movement): void;

    /**
     * Adds a movement another context has to point at, and writes it at once (inside the command's transaction) so
     * its id is known.
     */
    public function addNow(FundMovement $movement): int;

    /**
     * @throws NotFound cycle_not_found
     */
    public function cycle(int $id): PettyCashCycle;

    /** The open cycle, or null when the caja menor has not been used since the last one closed. */
    public function findOpenCycle(int $projectId): ?PettyCashCycle;

    /** Writes the cycle at once (inside the transaction) and answers its id. */
    public function saveCycle(PettyCashCycle $cycle): int;

    public function lastCycleNumber(int $projectId): int;

    /**
     * Holds the project's money until the transaction ends, so two writes cannot both spend the same balance
     * (two draws of the last contingency, a void racing a draw).
     */
    public function lock(int $projectId): void;

    /**
     * @throws NotFound movement_not_found
     */
    public function movement(int $id): FundMovement;

    /** From the stored movements that are not voided. */
    public function balances(int $projectId): Balances;

    /** The caja menor's open cycle; the next one is opened (with the current balance) when there is none. */
    public function openCycle(int $projectId, \DateTimeImmutable $now): PettyCashCycle;
}
