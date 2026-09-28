<?php

declare(strict_types=1);

namespace App\Finance\Application\Command;

use App\Finance\Application\Port\Currencies;
use App\Finance\Application\Port\FundedPlan;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\NewId;
use Psr\Clock\ClockInterface;

final readonly class DrawContingencyHandler implements CommandHandler
{
    public function __construct(
        private LedgerRepository $ledger,
        private FundedPlan $plan,
        private Currencies $currencies,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(DrawContingency $c): NewId
    {
        $this->ledger->lock($c->projectId);
        $targets = new FundingTargets($this->plan, $c->projectId);
        $stageId = $targets->openStage($c->stageId, 'stageId');
        $amount = FundingTargets::minor($c->amount, $this->currencies->of($c->projectId), 'amount');
        $draw = FundMovement::contingencyDraw($c->projectId, $stageId, $amount, $this->ledger->balances($c->projectId), $c->date, $c->reason, $c->actorId, $this->clock->now());
        $this->ledger->add($draw);

        return NewId::of($draw);
    }
}
