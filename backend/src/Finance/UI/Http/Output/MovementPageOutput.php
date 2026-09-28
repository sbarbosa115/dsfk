<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

use App\Shared\UI\Http\PageOutput;

/**
 * @extends PageOutput<MovementOutput>
 */
final readonly class MovementPageOutput extends PageOutput
{
    /**
     * @param list<MovementOutput> $items
     */
    public function __construct(array $items, int $total, int $page, int $perPage)
    {
        parent::__construct($items, $total, $page, $perPage);
    }
}
