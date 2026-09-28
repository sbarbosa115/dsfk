<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

/**
 * One page of a list, as every list endpoint answers: {items, total, page, perPage}. Each endpoint subclasses it
 * to name its items' type for the API schema.
 *
 * @template T
 */
abstract readonly class PageOutput
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
