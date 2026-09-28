<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

/**
 * Marks an integer column that holds the id of another bounded context's record. The entity keeps a plain id,
 * and the schema still gets the foreign key and its index (see CrossContextForeignKeys), so the database keeps
 * its integrity and `doctrine:migrations:diff` matches the existing schema.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class References
{
    public function __construct(public string $table, public ?string $onDelete = null)
    {
    }
}
