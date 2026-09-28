<?php

declare(strict_types=1);

namespace App\Planning\Application\Port;

/** What Planning needs from the Project context. */
interface ProjectCatalog
{
    /**
     * @return array{name: string, currency: string, status: string}
     */
    public function describe(int $projectId): array;

    /** ISO 4217 code: every amount of the plan is in it. */
    public function currency(int $projectId): string;

    /** Approving the budget makes a draft project active (in the same transaction). */
    public function activate(int $projectId): void;
}
