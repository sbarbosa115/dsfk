<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence;

use App\Audit\Application\Query\AuditQueries;
use App\Audit\Domain\Model\AuditRecord;
use App\Shared\Application\Query\Page;
use App\Shared\Infrastructure\Doctrine\Search;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final readonly class DoctrineAuditQueries implements AuditQueries
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function page(?int $projectId, ?string $entityType, ?string $search, int $page, int $perPage): Page
    {
        $qb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(AuditRecord::class, 'a')
            ->orderBy('a.id', 'DESC');
        if (null !== $projectId) {
            $qb->andWhere('a.projectId = :project')->setParameter('project', $projectId);
        }
        if (null !== $entityType && '' !== $entityType) {
            $qb->andWhere('a.entityType = :type')->setParameter('type', $entityType);
        }
        Search::apply($qb, $search, ['a.userName']);
        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $paginator = new Paginator($qb, fetchJoinCollection: false);

        return new Page(array_values(iterator_to_array($paginator)), \count($paginator));
    }

    public function entityTypes(): array
    {
        /** @var list<string> $types */
        $types = $this->em->createQueryBuilder()
            ->select('DISTINCT a.entityType')
            ->from(AuditRecord::class, 'a')
            ->orderBy('a.entityType', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return $types;
    }
}
