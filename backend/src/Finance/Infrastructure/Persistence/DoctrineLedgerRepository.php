<?php

declare(strict_types=1);

namespace App\Finance\Infrastructure\Persistence;

use App\Finance\Application\Query\FinanceQueries;
use App\Finance\Domain\Model\Balances;
use App\Finance\Domain\Model\CycleStatus;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Model\LedgerEntry;
use App\Finance\Domain\Model\MovementType;
use App\Finance\Domain\Model\PettyCashCycle;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Shared\Application\Query\Page;
use App\Shared\Domain\Error\NotFound;
use App\Shared\Infrastructure\Doctrine\Search;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final readonly class DoctrineLedgerRepository implements LedgerRepository, FinanceQueries
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function add(FundMovement $movement): void
    {
        $this->em->persist($movement);
    }

    public function lock(int $projectId): void
    {
        // The project row is the one row every money write of the project shares; the lock lasts until the
        // command bus commits.
        $this->em->getConnection()->executeQuery('SELECT id FROM project WHERE id = ? FOR UPDATE', [$projectId]);
    }

    public function movement(int $id): FundMovement
    {
        return $this->em->find(FundMovement::class, $id) ?? throw new NotFound('movement_not_found');
    }

    public function balances(int $projectId): Balances
    {
        /** @var list<array{account: LedgerAccount|string, stageId: int|string|null, type: MovementType|string, inflow: int|string|null, outflow: int|string|null}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('e.account AS account', 'e.stageId AS stageId', 'm.type AS type')
            ->addSelect('SUM(CASE WHEN e.amount > 0 THEN e.amount ELSE 0 END) AS inflow')
            ->addSelect('SUM(CASE WHEN e.amount < 0 THEN e.amount ELSE 0 END) AS outflow')
            ->from(LedgerEntry::class, 'e')
            ->join('e.movement', 'm')
            ->where('e.projectId = :project')
            ->andWhere('m.voidedAt IS NULL')
            ->groupBy('e.account', 'e.stageId', 'm.type')
            ->setParameter('project', $projectId)
            ->getQuery()
            ->getArrayResult();

        $flows = [];
        foreach ($rows as $row) {
            $account = $row['account'] instanceof LedgerAccount ? $row['account'] : LedgerAccount::from($row['account']);
            $type = $row['type'] instanceof MovementType ? $row['type'] : MovementType::from($row['type']);
            $key = Balances::key($account, null === $row['stageId'] ? null : (int) $row['stageId']);
            $flows[$key][$type->value] = ['in' => (int) $row['inflow'], 'out' => (int) $row['outflow']];
        }

        return new Balances($flows);
    }

    public function openCycle(int $projectId, \DateTimeImmutable $now): PettyCashCycle
    {
        $cycles = $this->em->getRepository(PettyCashCycle::class);
        $open = $cycles->findOneBy(['projectId' => $projectId, 'status' => CycleStatus::Open]);
        if (null !== $open) {
            return $open;
        }
        $last = $cycles->findOneBy(['projectId' => $projectId], ['number' => 'DESC']);
        $cycle = new PettyCashCycle($projectId, ($last?->getNumber() ?? 0) + 1, $this->balances($projectId)->of(LedgerAccount::PettyCash), $now);
        $this->em->persist($cycle);

        return $cycle;
    }

    public function projectOfMovement(int $movementId): ?int
    {
        return $this->em->find(FundMovement::class, $movementId)?->getProjectId();
    }

    public function fundingPage(int $projectId, ?string $search, int $page, int $perPage): Page
    {
        $qb = $this->em->createQueryBuilder()
            ->select('m', 'e')
            ->from(FundMovement::class, 'm')
            ->leftJoin('m.entries', 'e')
            ->where('m.projectId = :project')
            ->andWhere('m.type IN (:types)')
            ->setParameter('project', $projectId)
            ->setParameter('types', MovementType::funding())
            ->orderBy('m.date', 'DESC')
            ->addOrderBy('m.id', 'DESC');
        Search::apply($qb, $search, ['m.reference', 'm.note']);
        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $paginator = new Paginator($qb, fetchJoinCollection: true);

        return new Page(array_values(iterator_to_array($paginator)), \count($paginator));
    }

    public function usesCategory(int $categoryId): bool
    {
        return null !== $this->em->getRepository(LedgerEntry::class)->findOneBy(['categoryId' => $categoryId]);
    }
}
