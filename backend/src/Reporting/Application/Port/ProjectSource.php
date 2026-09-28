<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

/** The Project context. */
interface ProjectSource
{
    /**
     * @return list<array{id: int, name: string, currency: string, status: string}> by name
     */
    public function all(): array;

    /**
     * @return array{id: int, name: string, currency: string, status: string}
     */
    public function info(int $projectId): array;
}
