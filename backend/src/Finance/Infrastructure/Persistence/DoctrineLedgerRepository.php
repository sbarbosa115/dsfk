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
use Doctrine\DBAL\LockMode;
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

    public function addNow(FundMovement $movement): int
    {
        $this->em->persist($movement);
        $this->em->flush();

        return (int) $movement->getId();
    }

    public function cycle(int $id): PettyCashCycle
    {
        return $this->em->find(PettyCashCycle::class, $id) ?? throw new NotFound('cycle_not_found');
    }

    public function findOpenCycle(int $projectId): ?PettyCashCycle
    {
        return $this->em->getRepository(PettyCashCycle::class)->findOneBy(['projectId' => $projectId, 'status' => CycleStatus::Open]);
    }

    public function saveCycle(PettyCashCycle $cycle): int
    {
        $this->em->persist($cycle);
        $this->em->flush();

        return (int) $cycle->getId();
    }

    public function lastCycleNumber(int $projectId): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('MAX(c.number)')
            ->from(PettyCashCycle::class, 'c')
            ->where('c.projectId = :project')
            ->setParameter('project', $projectId)
            ->getQuery()
            ->getSingleScalarResult();
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

    public function movementForUpdate(int $id): FundMovement
    {
        return $this->em->find(FundMovement::class, $id, LockMode::PESSIMISTIC_WRITE) ?? throw new NotFound('movement_not_found');
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
        $open = $this->findOpenCycle($projectId);
        if (null !== $open) {
            return $open;
        }
        $cycle = new PettyCashCycle($projectId, $this->lastCycleNumber($projectId) + 1, $this->balances($projectId)->of(LedgerAccount::PettyCash), $now);
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

    public function movementDates(array $movementIds): array
    {
        $dates = [];
        foreach ([] === $movementIds ? [] : $this->em->getRepository(FundMovement::class)->findBy(['id' => $movementIds]) as $movement) {
            $dates[(int) $movement->getId()] = $movement->getDate()->format('Y-m-d');
        }

        return $dates;
    }

    public function projectOfCycle(int $cycleId): ?int
    {
        return $this->em->find(PettyCashCycle::class, $cycleId)?->getProjectId();
    }

    public function cycles(int $projectId): array
    {
        return $this->em->getRepository(PettyCashCycle::class)->findBy(['projectId' => $projectId], ['number' => 'DESC']);
    }

    public function cycleMovements(int $cycleId): array
    {
        return $this->em->createQueryBuilder()
            ->select('m', 'e')
            ->from(FundMovement::class, 'm')
            ->leftJoin('m.entries', 'e')
            ->where('IDENTITY(m.pettyCashCycle) = :cycle')
            ->setParameter('cycle', $cycleId)
            ->orderBy('m.date', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function fundingTotals(int $projectId): array
    {
        $b = $this->balances($projectId);
        /** @var list<array{account: LedgerAccount|string, stageId: int|string|null}> $keys */
        $keys = $this->em->createQueryBuilder()
            ->select('DISTINCT e.account AS account', 'e.stageId AS stageId')
            ->from(LedgerEntry::class, 'e')
            ->where('e.projectId = :project')
            ->setParameter('project', $projectId)
            ->getQuery()
            ->getArrayResult();
        $deposited = 0;
        $available = 0;
        foreach ($keys as $row) {
            $account = $row['account'] instanceof LedgerAccount ? $row['account'] : LedgerAccount::from($row['account']);
            $key = Balances::key($account, null === $row['stageId'] ? null : (int) $row['stageId']);
            $deposited += $b->in($key, MovementType::Deposit);
            $available += $b->balance($key);
        }

        return ['deposited' => $deposited, 'available' => $available, 'pettyCash' => $b->of(LedgerAccount::PettyCash)];
    }

    public function monthlyDeposits(int $projectId, \DateTimeImmutable $from): array
    {
        /** @var list<array{date: \DateTimeImmutable, amount: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('m.date AS date', 'm.amount AS amount')
            ->from(FundMovement::class, 'm')
            ->where('m.projectId = :project')
            ->andWhere('m.type = :type')
            ->andWhere('m.voidedAt IS NULL')
            ->andWhere('m.date >= :from')
            ->setParameter('project', $projectId)
            ->setParameter('type', MovementType::Deposit)
            ->setParameter('from', $from->format('Y-m-d'))
            ->getQuery()
            ->getArrayResult();
        $months = [];
        foreach ($rows as $row) {
            $month = $row['date']->format('Y-m');
            $months[$month] = ($months[$month] ?? 0) + (int) $row['amount'];
        }

        return $months;
    }

    public function unsignedCycles(int $projectId): int
    {
        return $this->em->getRepository(PettyCashCycle::class)->count(['projectId' => $projectId, 'status' => CycleStatus::Closed]);
    }

    public function cycleFacts(int $cycleId): ?array
    {
        $c = $this->em->find(PettyCashCycle::class, $cycleId);

        return null === $c ? null : [
            'projectId' => $c->getProjectId(),
            'number' => $c->getNumber(),
            'closingBalance' => (int) $c->getClosingBalance(),
            'closedById' => $c->getClosedById(),
            'note' => $c->getClosingNote(),
        ];
    }

    public function lastPettyCashTopUp(int $projectId): int
    {
        $amount = $this->em->createQueryBuilder()
            ->select('e.amount')
            ->from(LedgerEntry::class, 'e')
            ->join('e.movement', 'm')
            ->where('e.projectId = :project')
            ->andWhere('e.account = :account')
            ->andWhere('m.type = :type')
            ->andWhere('m.voidedAt IS NULL')
            ->setParameter('project', $projectId)
            ->setParameter('account', LedgerAccount::PettyCash)
            ->setParameter('type', MovementType::Deposit)
            ->orderBy('m.date', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return (int) $amount;
    }

    public function usesCategory(int $categoryId): bool
    {
        return null !== $this->em->getRepository(LedgerEntry::class)->findOneBy(['categoryId' => $categoryId]);
    }
}
