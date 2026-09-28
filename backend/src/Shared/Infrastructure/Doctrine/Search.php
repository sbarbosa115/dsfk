<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use Doctrine\ORM\QueryBuilder;

/** The `?q=` of every list: a case-insensitive substring over some fields, with % and _ matched literally. */
final class Search
{
    /**
     * @param list<string> $fields DQL paths, e.g. ['u.fullName', 'u.email']
     */
    public static function apply(QueryBuilder $qb, ?string $term, array $fields): void
    {
        if (null === $term || '' === trim($term)) {
            return;
        }
        $or = array_map(static fn (string $field): string => "LOWER({$field}) LIKE :search ESCAPE '!'", $fields);
        $qb->andWhere(implode(' OR ', $or))
            ->setParameter('search', '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower(trim($term))).'%');
    }
}
