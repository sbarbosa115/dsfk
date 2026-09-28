<?php

declare(strict_types=1);

namespace App\Document\Application\Query;

interface AttachmentQueries
{
    public function find(int $id): ?AttachmentView;

    /**
     * @param list<int> $movementIds
     *
     * @return array<int, list<AttachmentView>> by movement id, oldest first
     */
    public function ofMovements(array $movementIds): array;

    /**
     * @param list<int> $expenseIds
     *
     * @return array<int, list<AttachmentView>> by expense id, oldest first
     */
    public function ofExpenses(array $expenseIds): array;
}
