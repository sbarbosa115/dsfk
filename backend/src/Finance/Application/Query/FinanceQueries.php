<?php

declare(strict_types=1);

namespace App\Finance\Application\Query;

use App\Finance\Domain\Model\FundMovement;
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

    /** Whether any ledger entry earmarks money for the category (so Planning keeps it). */
    public function usesCategory(int $categoryId): bool;
}
