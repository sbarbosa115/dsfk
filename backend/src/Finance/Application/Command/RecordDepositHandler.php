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
use App\Shared\Domain\Error\InvalidValue;
use Psr\Clock\ClockInterface;

final readonly class RecordDepositHandler implements CommandHandler
{
    public function __construct(
        private LedgerRepository $ledger,
        private FundedPlan $plan,
        private Currencies $currencies,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RecordDeposit $c): NewId
    {
        $targets = new FundingTargets($this->plan, $c->projectId);
        $currency = $this->currencies->of($c->projectId);
        $deposit = FundMovement::deposit($c->projectId, $c->date, $c->method, $c->reference, $c->note, $c->actorId, $this->clock->now());

        foreach ($c->allocations as $i => $allocation) {
            $path = "allocations[$i]";
            $amount = FundingTargets::minor($allocation->amount, $currency, "$path.amount");
            $stageId = null;
            $categoryId = null;
            if (LedgerAccount::Stage === $allocation->destination) {
                $stageId = $targets->openStage($allocation->stageId, "$path.stageId");
                $categoryId = $targets->category($allocation->categoryId, "$path.categoryId");
            }
            try {
                $deposit->allocate($allocation->destination, $amount, $stageId, $categoryId);
            } catch (InvalidValue $e) {
                throw FundingTargets::prefixed($e, $path);
            }
        }
        if ([] === $deposit->getEntries()) {
            throw InvalidValue::field('allocations', 'Agrega al menos una distribución.');
        }
        if ($deposit->touchesPettyCash()) {
            $deposit->assignTo($this->ledger->openCycle($c->projectId, $this->clock->now()));
        }
        $this->ledger->add($deposit);

        return NewId::of($deposit);
    }
}
