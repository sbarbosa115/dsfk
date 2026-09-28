<?php

declare(strict_types=1);

namespace App\Shared\Application\Query;

/**
 * One page of a query's rows and how many rows there are in all.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param list<T> $items
     */
    public function __construct(public array $items, public int $total)
    {
    }
}
