<?php

declare(strict_types=1);

namespace App\Tests\Unit\Finance;

use App\Finance\Domain\Model\Balances;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Model\MovementType;
use App\Finance\Domain\Model\PaymentMethod;
use App\Finance\Domain\Model\PettyCashCycle;
use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use PHPUnit\Framework\TestCase;

final class FundMovementTest extends TestCase
{
    private const PROJECT = 7;
    private const ADMIN = 1;

    public function testADepositIsSplitIntoEntriesAndItsAmountIsTheirSum(): void
    {
        $deposit = $this->deposit();
        $deposit->allocate(LedgerAccount::Stage, 150_000_00, stageId: 11, categoryId: 3);
        $deposit->allocate(LedgerAccount::PettyCash, 30_000_00);
        $deposit->allocate(LedgerAccount::Contingency, 20_000_00);

        self::assertSame(MovementType::Deposit, $deposit->getType());
        self::assertSame(200_000_00, $deposit->getAmount());
        self::assertCount(3, $deposit->getEntries());
        self::assertSame(11, $deposit->getEntries()[0]->getStageId());
        self::assertSame(3, $deposit->getEntries()[0]->getCategoryId());
        self::assertTrue($deposit->touchesPettyCash());
        self::assertSame('TRX-1', $deposit->getReference());
    }

    public function testAnAllocationMustBePositiveAndAStageEntryNeedsItsStage(): void
    {
        $deposit = $this->deposit();

        $this->expectField(static fn () => $deposit->allocate(LedgerAccount::Stage, 0, stageId: 11), 'amount');
        $this->expectField(static fn () => $deposit->allocate(LedgerAccount::Stage, 100), 'stageId');
        $this->expectField(static fn () => $deposit->allocate(LedgerAccount::PettyCash, 100, stageId: 11), 'stageId');
    }

    public function testADrawCannotTakeMoreThanTheContingencyHolds(): void
    {
        $balances = new Balances(['CONTINGENCY' => ['DEPOSIT' => ['in' => 200_000, 'out' => 0]]]);

        $this->expectField(static fn () => FundMovement::contingencyDraw(self::PROJECT, 12, 200_001, $balances, new \DateTimeImmutable('2026-10-01'), 'Acero', self::ADMIN, self::now()), 'amount');

        $draw = FundMovement::contingencyDraw(self::PROJECT, 12, 150_000, $balances, new \DateTimeImmutable('2026-10-01'), 'Acero', self::ADMIN, self::now());
        self::assertSame(150_000, $draw->getAmount());
        self::assertSame([-150_000, 150_000], array_map(static fn ($e) => $e->getAmount(), $draw->getEntries()));
        self::assertSame('Acero', $draw->getNote());
    }

    public function testACarryOverMovesTheLeftoverToTheNextStageOrToTheContingency(): void
    {
        $next = FundMovement::carryOver(self::PROJECT, 11, 12, 500, new \DateTimeImmutable('2026-10-01'), self::ADMIN, self::now());
        self::assertSame([[LedgerAccount::Stage, 11, -500], [LedgerAccount::Stage, 12, 500]], self::entries($next));

        $last = FundMovement::carryOver(self::PROJECT, 12, null, 500, new \DateTimeImmutable('2026-10-01'), self::ADMIN, self::now());
        self::assertSame([[LedgerAccount::Stage, 12, -500], [LedgerAccount::Contingency, null, 500]], self::entries($last));
    }

    public function testVoidingKeepsTheRecordButNeverOverdrawsAnAccount(): void
    {
        $deposit = $this->deposit();
        $deposit->allocate(LedgerAccount::Contingency, 1000);
        $spent = new Balances(['CONTINGENCY' => ['DEPOSIT' => ['in' => 1000, 'out' => 0], 'CONTINGENCY_DRAW' => ['in' => 0, 'out' => -400]]]);

        $this->expectConflict(static fn () => $deposit->void($spent, self::ADMIN, 'Error', self::now()), 'void_would_overdraw');

        $untouched = new Balances(['CONTINGENCY' => ['DEPOSIT' => ['in' => 1000, 'out' => 0]]]);
        $deposit->void($untouched, self::ADMIN, '  Depósito duplicado ', self::now());
        self::assertTrue($deposit->isVoided());
        self::assertSame('Depósito duplicado', $deposit->getVoidReason());
        self::assertSame(self::ADMIN, $deposit->getVoidedById());

        $this->expectConflict(static fn () => $deposit->void($untouched, self::ADMIN, 'Otra vez', self::now()), 'movement_already_voided');
    }

    public function testCarryOversAreNotVoidedByHand(): void
    {
        $carry = FundMovement::carryOver(self::PROJECT, 11, 12, 500, self::now(), self::ADMIN, self::now());

        $this->expectConflict(static fn () => $carry->void(new Balances([]), self::ADMIN, 'x', self::now()), 'movement_not_voidable');
    }

    public function testAMovementOfAClosedCajaMenorCycleCannotChange(): void
    {
        $deposit = $this->deposit();
        $deposit->allocate(LedgerAccount::PettyCash, 1000);
        $cycle = new PettyCashCycle(self::PROJECT, 1, 0, self::now());
        $deposit->assignTo($cycle);
        $cycle->close(2, 1000, 'Se acabó', self::now());

        $this->expectConflict(static fn () => $deposit->void(new Balances(['PETTY_CASH' => ['DEPOSIT' => ['in' => 1000, 'out' => 0]]]), self::ADMIN, 'x', self::now()), 'cycle_closed');
    }

    public function testBalancesExplainEachAccount(): void
    {
        $b = new Balances([
            'STAGE:11' => ['DEPOSIT' => ['in' => 1000, 'out' => 0], 'CARRYOVER' => ['in' => 0, 'out' => -600]],
            'STAGE:12' => ['CARRYOVER' => ['in' => 600, 'out' => 0]],
        ]);

        self::assertSame(400, $b->stage(11));
        self::assertSame(600, $b->stage(12));
        self::assertSame(600, $b->out(Balances::key(LedgerAccount::Stage, 11), MovementType::Carryover));
        self::assertSame(1000, $b->in('STAGE:11', MovementType::Deposit));
        self::assertSame(0, $b->balance('PETTY_CASH'));
    }

    public function testAMovementCannotBeDatedInTheFuture(): void
    {
        $this->expectField(static fn () => FundMovement::deposit(self::PROJECT, new \DateTimeImmutable('2026-10-16'), PaymentMethod::Cash, null, null, self::ADMIN, self::now()), 'date');
    }

    private static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-10-15 10:00');
    }

    private function deposit(): FundMovement
    {
        return FundMovement::deposit(self::PROJECT, new \DateTimeImmutable('2026-10-01'), PaymentMethod::Transfer, ' TRX-1 ', null, self::ADMIN, self::now());
    }

    /**
     * @return list<array{LedgerAccount, ?int, int}>
     */
    private static function entries(FundMovement $movement): array
    {
        return array_map(static fn ($e) => [$e->getAccount(), $e->getStageId(), $e->getAmount()], $movement->getEntries());
    }

    private function expectField(callable $action, string $field): void
    {
        try {
            $action();
            self::fail("Expected a field error on $field");
        } catch (InvalidValue $e) {
            self::assertArrayHasKey($field, $e->extra['violations'] ?? []);
        }
    }

    private function expectConflict(callable $action, string $code): void
    {
        try {
            $action();
            self::fail("Expected $code");
        } catch (Conflict $e) {
            self::assertSame($code, $e->getMessage());
        }
    }
}
