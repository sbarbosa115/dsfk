<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Presenter;

use App\Document\Application\Query\AttachmentQueries;
use App\Document\Application\Query\AttachmentView;
use App\Expense\Application\Query\ExpenseDirectory;
use App\Finance\Application\Port\Currencies;
use App\Finance\Application\Port\People;
use App\Finance\Application\Query\FinanceQueries;
use App\Finance\Domain\Model\CycleStatus;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Model\MovementType;
use App\Finance\Domain\Model\PettyCashCycle;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Finance\UI\Http\Output\AttachmentOutput;
use App\Finance\UI\Http\Output\CycleMovementOutput;
use App\Finance\UI\Http\Output\CycleOutput;
use App\Finance\UI\Http\Output\PettyCashOutput;
use App\Finance\UI\Http\Output\PettyCashPermissionsOutput;
use App\Shared\Domain\Money\MinorUnits;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;

/** The caja menor: its balance, the current cycle with its movements, and the closed cycles. */
final readonly class PettyCashPresenter
{
    public function __construct(
        private LedgerRepository $ledger,
        private FinanceQueries $queries,
        private Currencies $currencies,
        private People $people,
        private ExpenseDirectory $expenses,
        private AttachmentQueries $attachments,
        private ProjectGuard $guard,
    ) {
    }

    public function present(int $projectId): PettyCashOutput
    {
        $currency = $this->currencies->of($projectId);
        $balance = $this->ledger->balances($projectId)->of(LedgerAccount::PettyCash);
        $cycles = $this->queries->cycles($projectId);
        $open = null;
        $closed = [];
        foreach ($cycles as $cycle) {
            if ($cycle->isOpen()) {
                $open = $cycle;
            } else {
                $closed[] = $cycle;
            }
        }
        // Nothing has used the caja menor since the last close: the next cycle opens with its balance.
        $current = null !== $open
            ? $this->cycle($open, $currency, true)
            : new CycleOutput(null, \count($cycles) + 1, CycleStatus::Open->value, null, null, null, null, null, null, $this->money($balance, $currency), $this->money(0, $currency), $this->money(0, $currency), $this->money(0, $currency), $this->money($balance, $currency), []);

        return new PettyCashOutput(
            $currency,
            $this->money($balance, $currency),
            $current,
            array_map(fn (PettyCashCycle $c): CycleOutput => $this->cycle($c, $currency, false), $closed),
            \count(array_filter($closed, static fn (PettyCashCycle $c): bool => CycleStatus::Closed === $c->getStatus())),
            new PettyCashPermissionsOutput(
                $this->guard->allows(ProjectPermission::PLAN, $projectId),
                $this->guard->allows(ProjectPermission::ADMINISTER, $projectId),
            ),
        );
    }

    public function detail(int $cycleId): CycleOutput
    {
        $cycle = $this->ledger->cycle($cycleId);

        return $this->cycle($cycle, $this->currencies->of($cycle->getProjectId()), true);
    }

    private function cycle(PettyCashCycle $cycle, string $currency, bool $withMovements): CycleOutput
    {
        $movements = $this->queries->cycleMovements((int) $cycle->getId());
        $totals = [MovementType::Deposit->value => 0, MovementType::Expense->value => 0, MovementType::Reimbursement->value => 0];
        $amounts = [];
        foreach ($movements as $m) {
            $amount = 0;
            foreach ($m->getEntries() as $entry) {
                if (LedgerAccount::PettyCash === $entry->getAccount()) {
                    $amount += $entry->getAmount();
                }
            }
            $amounts[(int) $m->getId()] = $amount;
            if (!$m->isVoided()) {
                $totals[$m->getType()->value] = ($totals[$m->getType()->value] ?? 0) + $amount;
            }
        }
        $running = $cycle->getOpeningBalance() + array_sum($totals);
        $names = $this->people->names(array_values(array_unique(array_filter([
            $cycle->getClosedById(),
            $cycle->getSignedOffById(),
            ...array_map(static fn (FundMovement $m): int => $m->getCreatedById(), $movements),
        ]))));

        return new CycleOutput(
            (int) $cycle->getId(),
            $cycle->getNumber(),
            $cycle->getStatus()->value,
            $cycle->getOpenedAt()->format(\DATE_ATOM),
            $cycle->getClosedAt()?->format(\DATE_ATOM),
            null === $cycle->getClosedById() ? null : ($names[$cycle->getClosedById()] ?? '—'),
            $cycle->getClosingNote(),
            $cycle->getSignedOffAt()?->format(\DATE_ATOM),
            null === $cycle->getSignedOffById() ? null : ($names[$cycle->getSignedOffById()] ?? '—'),
            $this->money($cycle->getOpeningBalance(), $currency),
            $this->money($totals[MovementType::Deposit->value], $currency),
            $this->money(-$totals[MovementType::Expense->value], $currency),
            $this->money(-$totals[MovementType::Reimbursement->value], $currency),
            $this->money($cycle->getClosingBalance() ?? $running, $currency),
            $withMovements ? $this->movements($movements, $amounts, $names, $currency) : null,
        );
    }

    /**
     * @param list<FundMovement> $movements
     * @param array<int, int>    $amounts   caja menor share by movement id
     * @param array<int, string> $names
     *
     * @return list<CycleMovementOutput>
     */
    private function movements(array $movements, array $amounts, array $names, string $currency): array
    {
        $ids = array_map(static fn (FundMovement $m): int => (int) $m->getId(), $movements);
        $expenseOf = $this->expenses->byMovements($ids);
        $proofs = $this->attachments->ofMovements($ids);
        $receipts = $this->attachments->ofExpenses(array_values($expenseOf));
        $files = static fn (array $views): array => array_values(array_map(static fn (AttachmentView $a): AttachmentOutput => new AttachmentOutput($a->id, $a->name, $a->mimeType, $a->size), $views));

        return array_map(fn (FundMovement $m): CycleMovementOutput => new CycleMovementOutput(
            (int) $m->getId(),
            $m->getType()->value,
            $m->getDate()->format('Y-m-d'),
            $this->money($amounts[(int) $m->getId()] ?? 0, $currency),
            (string) $m->getNote(),
            $names[$m->getCreatedById()] ?? '—',
            $m->isVoided(),
            $files(isset($expenseOf[(int) $m->getId()]) ? ($receipts[$expenseOf[(int) $m->getId()]] ?? []) : ($proofs[(int) $m->getId()] ?? [])),
        ), $movements);
    }

    private function money(int $minor, string $currency): string
    {
        return MinorUnits::toMajor($minor, $currency);
    }
}
