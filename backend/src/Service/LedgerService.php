<?php

namespace App\Service;

use App\Entity\FundMovement;
use App\Entity\LedgerEntry;
use App\Entity\Project;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use Doctrine\ORM\EntityManagerInterface;

class LedgerService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function balances(Project $project): Balances
    {
        $rows = $this->em->createQueryBuilder()
            ->select('e.account AS account', 'IDENTITY(e.stage) AS stageId', 'm.type AS type')
            ->addSelect('SUM(CASE WHEN e.amount > 0 THEN e.amount ELSE 0 END) AS inflow')
            ->addSelect('SUM(CASE WHEN e.amount < 0 THEN e.amount ELSE 0 END) AS outflow')
            ->from(LedgerEntry::class, 'e')
            ->join('e.movement', 'm')
            ->where('e.project = :project')
            ->andWhere('m.voidedAt IS NULL')
            ->groupBy('e.account', 'e.stage', 'm.type')
            ->setParameter('project', $project)
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

    /**
     * Voiding must not leave any account with a negative balance
     * (e.g. a deposit whose money was already drawn or carried over).
     */
    public function void(FundMovement $movement, User $by, string $reason): void
    {
        $balances = $this->balances($movement->getProject());
        $effect = [];
        foreach ($movement->getEntries() as $entry) {
            $key = Balances::key($entry->getAccount(), $entry->getStage()?->getId());
            $effect[$key] = ($effect[$key] ?? 0) + $entry->getAmount();
        }
        foreach ($effect as $key => $amount) {
            if ($balances->balance($key) - $amount < 0) {
                throw new \DomainException('void_would_overdraw');
            }
        }
        $movement->void($by, $reason);
    }

    /**
     * Completes a stage and moves whatever is left to the next open stage, or to the
     * contingency fund when it is the last one.
     */
    public function completeStage(Stage $stage, \DateTimeImmutable $date, User $by): ?FundMovement
    {
        $stage->complete($date);

        $left = $this->balances($stage->getProject())->stage($stage->getId());
        if ($left <= 0) {
            return null;
        }

        $next = $this->nextOpenStage($stage);
        $movement = new FundMovement($stage->getProject(), MovementType::Carryover, $date, $by);
        $movement->addEntry(LedgerAccount::Stage, -$left, $stage);
        if (null !== $next) {
            $movement->addEntry(LedgerAccount::Stage, $left, $next);
        } else {
            $movement->addEntry(LedgerAccount::Contingency, $left);
        }
        $this->em->persist($movement);

        return $movement;
    }

    public function nextOpenStage(Stage $stage): ?Stage
    {
        foreach ($stage->getProject()->getStages() as $candidate) {
            if ($candidate->getPosition() > $stage->getPosition() && !$candidate->isCompleted()) {
                return $candidate;
            }
        }

        return null;
    }
}
