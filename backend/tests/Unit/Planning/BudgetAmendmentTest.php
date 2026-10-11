<?php

declare(strict_types=1);

namespace App\Tests\Unit\Planning;

use App\Planning\Domain\Model\Budget;
use App\Planning\Domain\Model\BudgetStatus;
use App\Planning\Domain\Model\Stage;
use App\Shared\Domain\Error\Conflict;
use PHPUnit\Framework\TestCase;

/** An approved budget grows only through amendments (Adiciones): new stages the Admin approves apart. */
final class BudgetAmendmentTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-10-01 10:00');
    }

    public function testADraftBudgetTakesNewStagesItselfAndASubmittedOneTakesNone(): void
    {
        $budget = new Budget(1);
        self::assertNull($budget->amendmentToEdit($this->now), 'a draft budget is still being written: its stages are its own');

        $budget->submit(2, [], $this->now);
        $this->expectConflict(fn () => $budget->amendmentToEdit($this->now), 'budget_locked');
    }

    public function testAnApprovedBudgetOpensOneAmendmentAtATimeAndKeepsItsOwnStagesLocked(): void
    {
        $budget = $this->approved();
        $original = new Stage(1, 'Cimentación', 0);

        $first = $budget->amendmentToEdit($this->now);
        self::assertNotNull($first);
        self::assertSame(1, $first->getNumber());
        self::assertSame(BudgetStatus::Draft, $first->getStatus());
        self::assertSame($first, $budget->amendmentToEdit($this->now), 'a second stage joins the open amendment');

        $added = new Stage(1, 'Acabados exteriores', 1);
        $added->joinAmendment($first);
        self::assertTrue($added->isPendingApproval());
        self::assertFalse($original->isPendingApproval());

        $budget->assertStageEditable($added);
        $this->expectConflict(static fn () => $budget->assertStageEditable($original), 'budget_locked');
        $this->expectConflict(static fn () => $budget->changeContingency(1), 'budget_locked');
        self::assertSame(BudgetStatus::Approved, $budget->getStatus(), 'the budget itself stays approved');
    }

    public function testAnAmendmentIsSubmittedReturnedAndApprovedLikeTheBudgetWas(): void
    {
        $budget = $this->approved();
        $amendment = $budget->amendmentToEdit($this->now);
        self::assertNotNull($amendment);
        $added = new Stage(1, 'Acabados exteriores', 1);
        $added->joinAmendment($amendment);

        self::assertSame($amendment, $budget->submit(2, [], $this->now));
        self::assertSame(BudgetStatus::Submitted, $amendment->getStatus());
        $this->expectConflict(static fn () => $budget->assertStageEditable($added), 'budget_locked');
        $this->expectConflict(fn () => $budget->amendmentToEdit($this->now), 'budget_locked');

        self::assertSame($amendment, $budget->returnToDraft(1, ' Falta el hito de entrega ', $this->now));
        self::assertSame(BudgetStatus::Returned, $amendment->getStatus());
        $budget->assertStageEditable($added);

        $budget->submit(2, [], $this->now);
        $later = $this->now->modify('+1 day');
        self::assertSame($amendment, $budget->approve(1, [], $later));
        self::assertSame(BudgetStatus::Approved, $amendment->getStatus());
        self::assertSame($later, $amendment->getApprovedAt());
        self::assertFalse($added->isPendingApproval());
        $this->expectConflict(static fn () => $budget->assertStageEditable($added), 'budget_locked');

        $events = array_values(array_filter($budget->getEvents(), static fn ($e): bool => null !== $e->getAmendment()));
        self::assertSame(['SUBMITTED', 'RETURNED', 'SUBMITTED', 'APPROVED'], array_map(static fn ($e) => $e->getStatus()->value, $events));
        self::assertSame('Falta el hito de entrega', $events[1]->getComment());

        self::assertSame(2, $budget->amendmentToEdit($this->now)?->getNumber(), 'the next one opens once the first is approved');
        $this->expectConflict(fn () => $budget->approve(1, [], $this->now), 'budget_not_submitted');
    }

    public function testOnlyTheOpenAmendmentsStagesAreCheckedBeforeSending(): void
    {
        $budget = $this->approved();
        $original = new Stage(1, 'Cimentación', 0);
        $amendment = $budget->amendmentToEdit($this->now);
        self::assertNotNull($amendment);
        $added = new Stage(1, 'Acabados exteriores', 1);
        $added->joinAmendment($amendment);

        self::assertSame([$added], $budget->underReview([$original, $added]));
        self::assertSame([$original], (new Budget(2))->underReview([$original]), 'a draft budget checks all its stages');
    }

    public function testAnEmptiedAmendmentGoesAway(): void
    {
        $budget = $this->approved();
        $amendment = $budget->amendmentToEdit($this->now);
        self::assertNotNull($amendment);
        $budget->submit(2, [], $this->now);
        $budget->returnToDraft(1, 'No hace falta', $this->now);

        $budget->dropAmendment($amendment);

        self::assertSame([], $budget->getAmendments());
        self::assertSame([], array_values(array_filter($budget->getEvents(), static fn ($e): bool => null !== $e->getAmendment())));
        self::assertSame(1, $budget->amendmentToEdit($this->now)?->getNumber());
    }

    public function testAStageWaitingForApprovalCannotStartNorMeetMilestones(): void
    {
        $budget = $this->approved();
        $amendment = $budget->amendmentToEdit($this->now);
        self::assertNotNull($amendment);
        $added = new Stage(1, 'Acabados exteriores', 1);
        $added->joinAmendment($amendment);
        $milestone = $added->addMilestone('Fachada', Stage::FULL_WEIGHT, null);

        $this->expectConflict(fn () => $added->start($this->now, $this->now), 'stage_not_approved');
        $this->expectConflict(fn () => $milestone->complete($this->now, 2, null, $this->now), 'stage_not_approved');

        $budget->submit(2, [], $this->now);
        $budget->approve(1, [], $this->now);
        $added->start($this->now, $this->now);
        self::assertSame('IN_PROGRESS', $added->getStatus()->value);
    }

    private function approved(): Budget
    {
        $budget = new Budget(1);
        $budget->submit(2, [], $this->now);
        $budget->approve(1, [], $this->now);

        return $budget;
    }

    private function expectConflict(callable $action, string $code): void
    {
        try {
            $action();
            self::fail("Expected $code");
        } catch (Conflict $e) {
            self::assertSame($code, $e->errorCode);
        }
    }
}
