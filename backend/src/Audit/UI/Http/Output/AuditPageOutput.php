<?php

declare(strict_types=1);

namespace App\Audit\UI\Http\Output;

use App\Shared\UI\Http\PageOutput;

/**
 * @extends PageOutput<AuditEntryOutput>
 */
final readonly class AuditPageOutput extends PageOutput
{
    /**
     * @param list<AuditEntryOutput> $items
     * @param list<string>           $entityTypes for the filter
     */
    public function __construct(array $items, int $total, int $page, int $perPage, public array $entityTypes)
    {
        parent::__construct($items, $total, $page, $perPage);
    }
}
