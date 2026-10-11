<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Application\Port\Currencies;
use App\Finance\Application\Port\FundedPlan;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\NewId;
use Psr\Clock\ClockInterface;

final readonly class AssignFromFundHandler implements CommandHandler
{
    public function __construct(
        private LedgerRepository $ledger,
        private FundedPlan $plan,
        private Currencies $currencies,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(AssignFromFund $c): NewId
    {
        $this->ledger->lock($c->projectId);
        $targets = new FundingTargets($this->plan, $c->projectId);
        $stageId = LedgerAccount::Stage === $c->destination ? $targets->openStage($c->stageId, 'stageId') : null;
        $amount = FundingTargets::minor($c->amount, $this->currencies->of($c->projectId), 'amount');
        $assignment = FundMovement::fundAssignment($c->projectId, $c->destination, $stageId, $amount, $this->ledger->balances($c->projectId), $c->date, $c->note, $c->actorId, $this->clock->now());
        if ($assignment->touchesPettyCash()) {
            $assignment->assignTo($this->ledger->openCycle($c->projectId, $this->clock->now()));
        }
        $this->ledger->add($assignment);

        return NewId::of($assignment);
    }
}
