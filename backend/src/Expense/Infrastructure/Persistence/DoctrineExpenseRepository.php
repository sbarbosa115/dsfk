<?php

declare(strict_types=1);

namespace App\Expense\Infrastructure\Persistence;

use App\Expense\Application\Query\ExpenseDirectory;
use App\Expense\Application\Query\ExpenseQueries;
use App\Expense\Domain\Model\Expense;
use App\Expense\Domain\Model\ExpenseStatus;
use App\Expense\Domain\Model\PaidFrom;
use App\Expense\Domain\Model\Reimbursement;
use App\Expense\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Query\Page;
use App\Shared\Domain\Error\NotFound;
use App\Shared\Infrastructure\Doctrine\Search;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final readonly class DoctrineExpenseRepository implements ExpenseRepository, ExpenseQueries, ExpenseDirectory
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function add(Expense $expense): void
    {
        $this->em->persist($expense);
        $this->em->flush();
    }

    public function addReimbursement(Reimbursement $reimbursement): void
    {
        $this->em->persist($reimbursement);
    }

    public function get(int $id): Expense
    {
        return $this->em->find(Expense::class, $id) ?? throw new NotFound('expense_not_found');
    }

    public function many(array $ids): array
    {
        return [] === $ids ? [] : $this->em->getRepository(Expense::class)->findBy(['id' => $ids], ['id' => 'ASC']);
    }

    public function forUpdate(int $id): Expense
    {
        return $this->em->find(Expense::class, $id, LockMode::PESSIMISTIC_WRITE) ?? throw new NotFound('expense_not_found');
    }

    public function manyForUpdate(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->em->createQueryBuilder()
            ->select('e')
            ->from(Expense::class, 'e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getResult();
    }

    public function projectOf(int $expenseId): ?int
    {
        return $this->em->find(Expense::class, $expenseId)?->getProjectId();
    }

    public function page(int $projectId, ?int $paidBy, ?string $search, array $statuses, ?int $stageId, int $page, int $perPage): Page
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(Expense::class, 'e')
            ->where('e.projectId = :project')
            ->setParameter('project', $projectId)
            ->orderBy('e.date', 'DESC')
            ->addOrderBy('e.id', 'DESC');
        if (null !== $paidBy) {
            $qb->andWhere('e.paidById = :paidBy')->setParameter('paidBy', $paidBy);
        }
        if ([] !== $statuses) {
            $qb->andWhere('e.status IN (:statuses)')->setParameter('statuses', $statuses);
        }
        if (null !== $stageId) {
            $qb->andWhere('e.stageId = :stage')->setParameter('stage', $stageId);
        }
        Search::apply($qb, $search, ['e.description', 'e.supplier', 'e.invoiceNumber']);
        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $paginator = new Paginator($qb, fetchJoinCollection: false);

        return new Page(array_values(iterator_to_array($paginator)), \count($paginator));
    }

    public function outOfPocketByStatus(int $projectId, ?int $paidBy): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('e.status AS status', 'COUNT(e.id) AS n', 'SUM(e.amount) AS total')
            ->from(Expense::class, 'e')
            ->where('e.projectId = :project')
            ->andWhere('e.paidFrom = :oop')
            ->setParameter('project', $projectId)
            ->setParameter('oop', PaidFrom::OutOfPocket)
            ->groupBy('e.status');
        if (null !== $paidBy) {
            $qb->andWhere('e.paidById = :paidBy')->setParameter('paidBy', $paidBy);
        }
        $byStatus = [];
        /** @var list<array{status: ExpenseStatus|string, n: int|string, total: int|string|null}> $rows */
        $rows = $qb->getQuery()->getArrayResult();
        foreach ($rows as $row) {
            $status = $row['status'] instanceof ExpenseStatus ? $row['status']->value : $row['status'];
            $byStatus[$status] = ['count' => (int) $row['n'], 'total' => (int) $row['total']];
        }

        return $byStatus;
    }

    public function spending(int $projectId): array
    {
        /** @var list<array{stageId: int|string, categoryId: int|string, total: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('e.stageId AS stageId', 'e.categoryId AS categoryId', 'SUM(e.amount) AS total')
            ->from(Expense::class, 'e')
            ->where('e.projectId = :project')
            ->andWhere('e.status IN (:spent)')
            ->groupBy('e.stageId', 'e.categoryId')
            ->setParameter('project', $projectId)
            ->setParameter('spent', ExpenseStatus::spent())
            ->getQuery()
            ->getArrayResult();
        $spent = [];
        foreach ($rows as $row) {
            $spent[(int) $row['stageId']][(int) $row['categoryId']] = (int) $row['total'];
        }

        return $spent;
    }

    public function monthlySpending(int $projectId, \DateTimeImmutable $from): array
    {
        /** @var list<array{date: \DateTimeImmutable, amount: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('e.date AS date', 'e.amount AS amount')
            ->from(Expense::class, 'e')
            ->where('e.projectId = :project')
            ->andWhere('e.status IN (:spent)')
            ->andWhere('e.date >= :from')
            ->setParameter('project', $projectId)
            ->setParameter('spent', ExpenseStatus::spent())
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

    public function pendingCount(int $projectId): int
    {
        return $this->em->getRepository(Expense::class)->count(['projectId' => $projectId, 'status' => [ExpenseStatus::Submitted, ExpenseStatus::PmApproved]]);
    }

    public function toReimburseCount(int $projectId): int
    {
        return $this->em->getRepository(Expense::class)->count(['projectId' => $projectId, 'status' => ExpenseStatus::Approved, 'paidFrom' => PaidFrom::OutOfPocket]);
    }

    public function facts(int $expenseId): ?array
    {
        $e = $this->em->find(Expense::class, $expenseId);

        return null === $e ? null : [
            'projectId' => $e->getProjectId(),
            'stageId' => $e->getStageId(),
            'categoryId' => $e->getCategoryId(),
            'amount' => $e->getAmount(),
            'description' => $e->getDescription(),
            'paidById' => $e->getPaidById(),
            'rejectionReason' => $e->getRejectionReason(),
        ];
    }

    public function usesCategory(int $categoryId): bool
    {
        return null !== $this->em->getRepository(Expense::class)->findOneBy(['categoryId' => $categoryId]);
    }

    public function byMovements(array $movementIds): array
    {
        if ([] === $movementIds) {
            return [];
        }
        $byMovement = [];
        foreach ($this->em->getRepository(Expense::class)->findBy(['movementId' => $movementIds]) as $expense) {
            $byMovement[(int) $expense->getMovementId()] = (int) $expense->getId();
        }

        return $byMovement;
    }

    public function paidBy(int $expenseId): ?int
    {
        return $this->em->find(Expense::class, $expenseId)?->getPaidById();
    }
}
