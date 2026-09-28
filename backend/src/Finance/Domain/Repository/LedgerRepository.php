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
     * @throws NotFound movement_not_found
     */
    public function movement(int $id): FundMovement;

    /** From the stored movements that are not voided. */
    public function balances(int $projectId): Balances;

    /** The caja menor's open cycle; the next one is opened (with the current balance) when there is none. */
    public function openCycle(int $projectId, \DateTimeImmutable $now): PettyCashCycle;
}
