<?php

declare(strict_types=1);

namespace App\Tests\Unit\Planning;

use App\Planning\Domain\Model\Budget;
use App\Planning\Domain\Model\BudgetStatus;
use App\Planning\Domain\Model\Category;
use App\Planning\Domain\Model\Stage;
use App\Planning\Domain\Service\PlanCompleteness;
use App\Planning\Domain\Service\Progress;
use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use PHPUnit\Framework\TestCase;

final class BudgetTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-10-01 10:00');
    }

    public function testAnIncompletePlanCannotBeSubmittedAndSaysWhy(): void
    {
        $stage = new Stage(1, 'Cimentación', 0);
        $this->setId($stage, 11);

        try {
            (new Budget(1))->submit(2, PlanCompleteness::issues([$stage]), $this->now);
            self::fail('an incomplete plan must be refused');
        } catch (InvalidValue $e) {
            self::assertSame('budget_incomplete', $e->errorCode);
            self::assertSame([['code' => 'stage_without_lines', 'stageId' => 11], ['code' => 'milestone_weights', 'stageId' => 11]], $e->extra['issues']);
        }
        self::assertSame([['code' => 'no_stages']], PlanCompleteness::issues([]));
    }

    public function testTheWorkflowRecordsEveryTransitionAndLocksOnApproval(): void
    {
        $budget = new Budget(1);
        $budget->changeContingency(50000000);
        $budget->submit(2, [], $this->now);

        try {
            $budget->changeContingency(1);
            self::fail('a submitted budget is locked');
        } catch (Conflict $e) {
            self::assertSame('budget_locked', $e->errorCode);
        }

        $budget->returnToDraft(1, ' Revisar concreto ', $this->now);
        self::assertTrue($budget->isEditable());
        $budget->submit(2, [], $this->now);
        $budget->approve(1, [], $this->now);

        self::assertSame(BudgetStatus::Approved, $budget->getStatus());
        self::assertSame($this->now, $budget->getApprovedAt());
        self::assertSame(['SUBMITTED', 'RETURNED', 'SUBMITTED', 'APPROVED'], array_map(static fn ($e) => $e->getStatus()->value, $budget->getEvents()));
        self::assertSame('Revisar concreto', $budget->getEvents()[1]->getComment());

        $this->expectExceptionObject(new Conflict('budget_locked'));
        $budget->changeContingency(1);
    }

    public function testOnlyASubmittedBudgetIsApprovedOrReturned(): void
    {
        $this->expectExceptionObject(new Conflict('budget_not_submitted'));

        (new Budget(1))->approve(1, [], $this->now);
    }

    public function testReturningNeedsAComment(): void
    {
        $budget = new Budget(1);
        $budget->submit(2, [], $this->now);

        $this->expectException(InvalidValue::class);

        $budget->returnToDraft(1, '   ', $this->now);
    }

    public function testProjectProgressWeighsEachStageByItsShareOfTheBudget(): void
    {
        $category = new Category(1, 'Materiales');
        $foundation = new Stage(1, 'Cimentación', 0);
        $foundation->addLine($category, 'x', 'm³', '12.5', 3500050);
        $foundation->addLine($category, 'y', 'gl', '1', 100000000);
        $foundation->addMilestone('Excavación', 4000, null)->complete($this->now, 1, null, $this->now);
        $foundation->addMilestone('Vaciado', 6000, null);
        $structure = new Stage(1, 'Estructura', 1);
        $structure->addLine($category, 'z', 'm³', '100', 3000000);

        // 40 % × 1,437,506.25 / 4,437,506.25
        self::assertSame(1295, Progress::ofProject([$foundation, $structure]));
        self::assertSame(3239, Progress::stageWeight($foundation, [$foundation, $structure]), 'share of the budget, half up');
        $unbudgeted = new Stage(1, 'Sin presupuesto', 2);
        $unbudgeted->addMilestone('Todo', 10000, null)->complete($this->now, 1, null, $this->now);
        self::assertSame(5000, Progress::ofProject([$unbudgeted, new Stage(1, 'Vacía', 3)]), 'with no budget at all, a plain average');
        self::assertSame(0, Progress::ofProject([]));
    }

    private function setId(object $entity, int $id): void
    {
        (new \ReflectionProperty($entity, 'id'))->setValue($entity, $id);
    }
}
