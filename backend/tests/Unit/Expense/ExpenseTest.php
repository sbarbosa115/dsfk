<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense;

use App\Expense\Domain\Model\Expense;
use App\Expense\Domain\Model\ExpenseStatus;
use App\Expense\Domain\Model\PaidFrom;
use App\Expense\Domain\Model\PayoutMethod;
use App\Expense\Domain\Model\Reimbursement;
use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\DomainError;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotAllowed;
use PHPUnit\Framework\TestCase;

final class ExpenseTest extends TestCase
{
    private const PROJECT = 7;
    private const PM = 2;
    private const LEAD = 3;
    private const ADMIN = 1;
    private const LIMIT = 500_000_00;

    public function testManagersSpendProjectMoneyAtOnceAndTeamLeadsAskFirst(): void
    {
        $fromStage = $this->expense(PaidFrom::Stage, self::PM);
        self::assertSame(ExpenseStatus::Approved, $fromStage->getStatus());
        self::assertTrue($fromStage->isSpent());

        $outOfPocket = $this->expense(PaidFrom::OutOfPocket, self::LEAD);
        self::assertSame(ExpenseStatus::Submitted, $outOfPocket->getStatus());
        self::assertFalse($outOfPocket->isSpent());
        self::assertSame(['CREATED'], array_map(static fn ($e) => $e->getType(), $outOfPocket->getEvents()));
    }

    public function testAnExpenseNeedsADescriptionAPositiveAmountAndAPastDate(): void
    {
        $this->expectField(fn () => $this->expense(PaidFrom::Stage, self::PM, description: '  '), 'description');
        $this->expectField(fn () => $this->expense(PaidFrom::Stage, self::PM, amount: 0), 'amount');
        $this->expectField(fn () => $this->expense(PaidFrom::Stage, self::PM, date: '2026-10-16'), 'date');
    }

    public function testApprovalNeedsAReceipt(): void
    {
        $expense = $this->expense(PaidFrom::OutOfPocket, self::LEAD);

        $this->expectCode(static fn () => $expense->approve(self::PM, isAdmin: false, limit: self::LIMIT, hasReceipt: false, now: self::now()), Conflict::class, 'receipt_required');
    }

    public function testThePmApprovesUpToTheLimitAndTheAdminAboveIt(): void
    {
        $small = $this->expense(PaidFrom::OutOfPocket, self::LEAD, amount: self::LIMIT);
        $small->approve(self::PM, isAdmin: false, limit: self::LIMIT, hasReceipt: true, now: self::now());
        self::assertSame(ExpenseStatus::Approved, $small->getStatus());

        $big = $this->expense(PaidFrom::OutOfPocket, self::LEAD, amount: self::LIMIT + 1);
        $big->approve(self::PM, isAdmin: false, limit: self::LIMIT, hasReceipt: true, now: self::now());
        self::assertSame(ExpenseStatus::PmApproved, $big->getStatus());
        $this->expectCode(static fn () => $big->approve(self::PM, isAdmin: false, limit: self::LIMIT, hasReceipt: true, now: self::now()), Conflict::class, 'expense_awaiting_admin');
        $this->expectCode(static fn () => $big->reject(self::PM, isAdmin: false, reason: 'No', now: self::now()), Conflict::class, 'expense_awaiting_admin');

        $big->approve(self::ADMIN, isAdmin: true, limit: self::LIMIT, hasReceipt: true, now: self::now());
        self::assertSame(ExpenseStatus::Approved, $big->getStatus());
        self::assertSame(['CREATED', 'PM_APPROVED', 'APPROVED'], array_map(static fn ($e) => $e->getType(), $big->getEvents()));
        $this->expectCode(static fn () => $big->approve(self::ADMIN, isAdmin: true, limit: self::LIMIT, hasReceipt: true, now: self::now()), Conflict::class, 'expense_invalid_status');
    }

    public function testARejectedExpenseIsCorrectedByItsOwnerAndGoesBackToThePm(): void
    {
        $expense = $this->expense(PaidFrom::OutOfPocket, self::LEAD);
        $this->expectField(static fn () => $expense->reject(self::PM, isAdmin: false, reason: ' ', now: self::now()), 'reason');
        $expense->reject(self::PM, isAdmin: false, reason: ' Falta la factura ', now: self::now());
        self::assertSame(ExpenseStatus::Rejected, $expense->getStatus());
        self::assertSame('Falta la factura', $expense->getRejectionReason());

        $this->expectCode(fn () => $this->correct($expense, self::PM), NotAllowed::class, 'forbidden');
        $this->correct($expense, self::LEAD);

        self::assertSame(ExpenseStatus::Submitted, $expense->getStatus());
        self::assertNull($expense->getRejectionReason());
        self::assertSame(90_000, $expense->getAmount());
        $edited = $expense->getEvents()[2];
        self::assertSame('EDITED', $edited->getType());
        self::assertSame(['stage' => 'Cimentación', 'category' => 'Materiales', 'date' => '2026-10-10', 'amount' => 120_000, 'description' => 'Cemento', 'supplier' => null, 'invoiceNumber' => null], $edited->getPrevious());
    }

    public function testAnApprovedExpenseCannotBeCorrected(): void
    {
        $expense = $this->expense(PaidFrom::Stage, self::PM);

        $this->expectCode(fn () => $this->correct($expense, self::PM), Conflict::class, 'expense_not_editable');
    }

    public function testOnlyAnApprovedExpenseNotYetPaidBackIsVoided(): void
    {
        $pending = $this->expense(PaidFrom::OutOfPocket, self::LEAD);
        $this->expectCode(static fn () => $pending->void(self::ADMIN, 'x', self::now()), Conflict::class, 'expense_invalid_status');

        $paid = $this->expense(PaidFrom::Stage, self::PM);
        $paid->void(self::ADMIN, ' Duplicado ', self::now());
        self::assertSame(ExpenseStatus::Voided, $paid->getStatus());
        self::assertFalse($paid->isSpent());
        self::assertSame('Duplicado', $paid->getEvents()[1]->getComment());

        $reimbursed = $this->approved();
        $reimbursed->markReimbursed(new Reimbursement(self::PROJECT, 40, PayoutMethod::Cash, null), self::PM, self::now());
        self::assertSame(ExpenseStatus::Reimbursed, $reimbursed->getStatus());
        self::assertTrue($reimbursed->isSpent());
        $this->expectCode(static fn () => $reimbursed->void(self::ADMIN, 'x', self::now()), Conflict::class, 'expense_invalid_status');
    }

    public function testOnlyApprovedOutOfPocketExpensesArePaidBack(): void
    {
        $fromStage = $this->expense(PaidFrom::Stage, self::PM);
        $this->expectCode(static fn () => $fromStage->markReimbursed(new Reimbursement(self::PROJECT, 40, PayoutMethod::Cash, null), self::PM, self::now()), Conflict::class, 'expense_not_reimbursable');

        $pending = $this->expense(PaidFrom::OutOfPocket, self::LEAD);
        $this->expectCode(static fn () => $pending->markReimbursed(new Reimbursement(self::PROJECT, 40, PayoutMethod::Cash, null), self::PM, self::now()), Conflict::class, 'expense_not_reimbursable');
    }

    private function approved(): Expense
    {
        $expense = $this->expense(PaidFrom::OutOfPocket, self::LEAD);
        $expense->approve(self::PM, isAdmin: false, limit: self::LIMIT, hasReceipt: true, now: self::now());

        return $expense;
    }

    private function expense(PaidFrom $paidFrom, int $by, int $amount = 120_000, string $description = 'Cemento', string $date = '2026-10-10'): Expense
    {
        return Expense::record(self::PROJECT, 11, 3, new \DateTimeImmutable($date), $amount, $description, null, null, $paidFrom, $by, self::now());
    }

    private function correct(Expense $expense, int $by): void
    {
        $expense->correct($by, 11, 3, new \DateTimeImmutable('2026-10-11'), 90_000, 'Cemento gris', 'Ferretería', 'F-1', ['stage' => 'Cimentación', 'category' => 'Materiales'], self::now());
    }

    private static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-10-15 10:00');
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

    /**
     * @param class-string<DomainError> $kind
     */
    private function expectCode(callable $action, string $kind, string $code): void
    {
        try {
            $action();
            self::fail("Expected $code");
        } catch (DomainError $e) {
            self::assertInstanceOf($kind, $e);
            self::assertSame($code, $e->errorCode);
        }
    }
}
