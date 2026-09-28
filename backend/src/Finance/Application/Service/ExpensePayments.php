<?php

declare(strict_types=1);

namespace App\Finance\Application\Service;

use App\Finance\Application\Port\Currencies;
use App\Finance\Application\Port\FundedPlan;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Money\MinorUnits;
use Psr\Clock\ClockInterface;

/**
 * The money side of expenses, called by the Expense context inside its command's transaction: paying an expense
 * from a stage or petty cash, giving it back when the expense is voided, and paying Team Leads back. Each
 * locks the project's money first, and refuses to take an account below zero.
 */
final readonly class ExpensePayments
{
    public function __construct(
        private LedgerRepository $ledger,
        private FundedPlan $plan,
        private Currencies $currencies,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param 'STAGE'|'PETTY_CASH' $source
     *
     * @return int the EXPENSE movement's id
     *
     * @throws InvalidValue insufficient_funds on "amount"; a completed stage on "stageId"
     */
    public function pay(int $projectId, string $source, int $stageId, int $categoryId, int $amount, \DateTimeImmutable $date, string $description, int $actorId): int
    {
        $this->ledger->lock($projectId);
        $from = LedgerAccount::from($source);
        if (LedgerAccount::Stage === $from) {
            foreach ($this->plan->stages($projectId) as $stage) {
                if ($stage->id === $stageId && $stage->isCompleted()) {
                    throw InvalidValue::field('stageId', 'The stage is already completed and holds no money.');
                }
            }
        }
        $movement = $this->funded($projectId, 'amount', fn () => FundMovement::spend($projectId, $from, $stageId, $categoryId, $amount, $this->ledger->balances($projectId), $date, $description, $actorId, $this->clock->now()));
        if ($movement->touchesPettyCash()) {
            $movement->assignTo($this->ledger->openCycle($projectId, $this->clock->now()));
        }

        return $this->ledger->addNow($movement);
    }

    /** The expense was voided: its money goes back to the stage or petty cash. */
    public function refund(int $movementId, int $actorId, string $reason): void
    {
        $movement = $this->ledger->movementForUpdate($movementId);
        $this->ledger->lock($movement->getProjectId());
        $movement->voidSpending($this->ledger->balances($movement->getProjectId()), $actorId, $reason, $this->clock->now());
    }

    /**
     * @return int the REIMBURSEMENT movement's id
     *
     * @throws InvalidValue insufficient_funds on "expenseIds"
     */
    public function payBack(int $projectId, int $total, \DateTimeImmutable $date, string $note, int $actorId): int
    {
        $this->ledger->lock($projectId);
        $movement = $this->funded($projectId, 'expenseIds', fn () => FundMovement::reimbursement($projectId, $total, $this->ledger->balances($projectId), $date, $note, $actorId, $this->clock->now()));
        $movement->assignTo($this->ledger->openCycle($projectId, $this->clock->now()));

        return $this->ledger->addNow($movement);
    }

    /**
     * @param \Closure(): FundMovement $create
     */
    private function funded(int $projectId, string $field, \Closure $create): FundMovement
    {
        try {
            return $create();
        } catch (InvalidValue $e) {
            if ('insufficient_funds' !== $e->errorCode) {
                throw $e;
            }
            $currency = $this->currencies->of($projectId);
            $available = \is_int($e->extra['available'] ?? null) ? $e->extra['available'] : 0;

            throw new InvalidValue('insufficient_funds', ['available' => MinorUnits::toMajor($available, $currency)] + InvalidValue::violation($field, 'Not enough funds. Available: %available%.', ['%available%' => MinorUnits::format($available, $currency)]));
        }
    }
}
