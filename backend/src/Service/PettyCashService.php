<?php

namespace App\Service;

use App\Entity\FundMovement;
use App\Entity\PettyCashCycle;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\CycleStatus;
use App\Enum\LedgerAccount;
use Doctrine\ORM\EntityManagerInterface;

class PettyCashService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LedgerService $ledger,
    ) {
    }

    /** The open cycle, created on first use. */
    public function currentCycle(Project $project): PettyCashCycle
    {
        $repository = $this->em->getRepository(PettyCashCycle::class);
        $open = $repository->findOneBy(['project' => $project, 'status' => CycleStatus::Open]);
        if (null !== $open) {
            return $open;
        }

        $last = $repository->findOneBy(['project' => $project], ['number' => 'DESC']);
        $cycle = new PettyCashCycle($project, ($last?->getNumber() ?? 0) + 1, $this->balance($project));
        $this->em->persist($cycle);

        return $cycle;
    }

    /** Tags a new movement with the open cycle when it touches the caja menor. */
    public function assign(FundMovement $movement): void
    {
        if ($movement->touchesPettyCash()) {
            $movement->setPettyCashCycle($this->currentCycle($movement->getProject()));
        }
    }

    /** Movements of a closed cycle are part of a reviewed summary and cannot change. */
    public function assertMutable(FundMovement $movement): void
    {
        $cycle = $movement->getPettyCashCycle();
        if (null !== $cycle && !$cycle->isOpen()) {
            throw new \DomainException('cycle_closed');
        }
    }

    public function close(Project $project, User $by, ?string $note): PettyCashCycle
    {
        $cycle = $this->currentCycle($project);
        $balance = $this->balance($project);
        $cycle->close($by, $balance, $note);
        $this->em->flush();

        $next = new PettyCashCycle($project, $cycle->getNumber() + 1, $balance);
        $this->em->persist($next);

        return $cycle;
    }

    public function balance(Project $project): int
    {
        return $this->ledger->balances($project)->balance(LedgerAccount::PettyCash->value);
    }
}
