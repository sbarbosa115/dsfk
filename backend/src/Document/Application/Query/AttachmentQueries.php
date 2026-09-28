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
}
