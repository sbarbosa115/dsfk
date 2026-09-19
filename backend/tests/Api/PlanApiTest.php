<?php

namespace App\Tests\Api;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectRole;

class PlanApiTest extends ApiTestCase
{
    use BuildsPlan;

    private User $admin;
    private User $pm;
    private User $lead;
    private Project $project;
    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin@example.com', admin: true);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->project = $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead]]);
        $this->base = '/api/projects/'.$this->project->getId();
    }

    public function testProjectManagerBuildsBudgetWithComputedTotals(): void
    {
        $this->loginAs($this->pm);

        $plan = $this->buildPlan();

        self::assertSame('DRAFT', $plan['budgetStatus']);
        self::assertSame([], $plan['issues']);
        self::assertSame(['Cimentación', 'Estructura'], array_column($plan['stages'], 'name'));

        $foundation = $plan['stages'][0];
        self::assertSame('437506.25', $foundation['lines'][0]['total']);
        self::assertSame('12.5', $foundation['lines'][0]['quantity']);
        self::assertSame('1437506.25', $foundation['budgetTotal']);
        self::assertSame(10000, $foundation['milestoneWeightTotal']);

        self::assertSame('4437506.25', $plan['budget']['stagesTotal']);
        self::assertSame('500000.00', $plan['budget']['contingency']);
        self::assertSame('4937506.25', $plan['budget']['total']);
        // Stage share of the budget: 1,437,506.25 / 4,437,506.25 ≈ 32.39%
        self::assertSame(3239, $foundation['weight']);
    }

    public function testTeamLeadSeesPlanWithoutMoneyAndCannotEdit(): void
    {
        $this->loginAs($this->pm);
        $this->buildPlan();
        $this->client->restart();
        $this->loginAs($this->lead);

        $plan = $this->request('GET', $this->base.'/plan');

        $this->assertStatus(200);
        self::assertArrayNotHasKey('budget', $plan);
        self::assertArrayNotHasKey('lines', $plan['stages'][0]);
        self::assertArrayNotHasKey('budgetTotal', $plan['stages'][0]);
        self::assertCount(2, $plan['stages'][0]['milestones']);
        self::assertFalse($plan['permissions']['edit']);

        $this->request('POST', $this->base.'/stages', ['name' => 'Acabados']);
        $this->assertStatus(403);
    }

    public function testOutsidersCannotSeeThePlan(): void
    {
        $this->loginAs($this->createUser('other@example.com'));

        $this->request('GET', $this->base.'/plan');

        $this->assertStatus(403);
    }

    public function testIncompleteBudgetCannotBeSubmitted(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->request('POST', $this->base.'/stages', ['name' => 'Cimentación']);
        $stageId = $plan['stages'][0]['id'];

        $data = $this->request('POST', $this->base.'/budget/submit');

        $this->assertStatus(422);
        self::assertSame('budget_incomplete', $data['error']);
        self::assertEqualsCanonicalizing(
            [['code' => 'stage_without_lines', 'stageId' => $stageId], ['code' => 'milestone_weights', 'stageId' => $stageId]],
            $data['issues'],
        );
    }

    public function testMilestoneWeightsCannotExceedOneHundredPercent(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->request('POST', $this->base.'/stages', ['name' => 'Cimentación']);
        $stageId = $plan['stages'][0]['id'];
        $this->request('POST', "/api/stages/$stageId/milestones", ['name' => 'Excavación', 'weight' => '60']);

        $data = $this->request('POST', "/api/stages/$stageId/milestones", ['name' => 'Vaciado', 'weight' => '40.01']);

        $this->assertStatus(422);
        self::assertArrayHasKey('weight', $data['violations']);
    }

    public function testApprovalWorkflowLocksTheBudget(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $lineId = $plan['stages'][0]['lines'][0]['id'];

        $this->request('POST', $this->base.'/budget/submit');
        $this->assertStatus(200);

        // Submitted: the PM can no longer edit, nor approve.
        $data = $this->request('DELETE', "/api/budget-lines/$lineId");
        $this->assertStatus(422);
        self::assertSame('budget_locked', $data['error']);
        $this->request('POST', $this->base.'/budget/approve');
        $this->assertStatus(403);

        // Admin returns it with a comment; the PM can edit and resubmit.
        $this->loginAs($this->admin);
        $this->request('POST', $this->base.'/budget/return', ['comment' => 'Revisar precio del concreto']);
        $this->assertStatus(200);
        $this->loginAs($this->pm);
        $this->request('PUT', "/api/budget-lines/$lineId", $this->line($plan['categories'][0]['id'], unitPrice: '36000'));
        $this->assertStatus(200);
        $this->request('POST', $this->base.'/budget/submit');

        $this->loginAs($this->admin);
        $plan = $this->request('POST', $this->base.'/budget/approve');
        $this->assertStatus(200);
        self::assertSame('APPROVED', $plan['budgetStatus']);
        self::assertSame(['SUBMITTED', 'RETURNED', 'SUBMITTED', 'APPROVED'], array_column($plan['budget']['events'], 'status'));
        self::assertSame('Revisar precio del concreto', $plan['budget']['events'][1]['comment']);
        self::assertSame('ACTIVE', $this->request('GET', $this->base)['status']);

        // Approved: locked for everyone, the Admin included.
        $data = $this->request('PUT', $this->base.'/budget/contingency', ['contingency' => '1']);
        $this->assertStatus(422);
        self::assertSame('budget_locked', $data['error']);
    }

    public function testMilestonesAreCompletedOnlyAfterApprovalAndDriveProgress(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $milestoneId = $plan['stages'][0]['milestones'][0]['id'];

        $data = $this->request('POST', "/api/milestones/$milestoneId/complete", ['completedAt' => date('Y-m-d')]);
        $this->assertStatus(422);
        self::assertSame('budget_not_approved', $data['error']);

        $this->approve();
        $this->loginAs($this->pm);

        $plan = $this->request('POST', "/api/milestones/$milestoneId/complete", ['completedAt' => date('Y-m-d'), 'notes' => 'Excavación terminada']);
        $this->assertStatus(200);
        $stage = $plan['stages'][0];
        self::assertSame(4000, $stage['progress']);
        self::assertSame('Pm', $stage['milestones'][0]['completedBy']['fullName']);
        // Project progress weights stages by budget: 40% × 1,437,506.25 / 4,437,506.25
        self::assertSame(1295, $plan['progress']);

        // Only the Admin can undo a completion.
        $this->request('POST', "/api/milestones/$milestoneId/reopen");
        $this->assertStatus(403);
        $this->loginAs($this->admin);
        $plan = $this->request('POST', "/api/milestones/$milestoneId/reopen");
        self::assertSame(0, $plan['stages'][0]['progress']);
    }

    public function testCompletionDateCannotBeInTheFuture(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->approve();
        $this->loginAs($this->pm);

        $data = $this->request('POST', '/api/milestones/'.$plan['stages'][0]['milestones'][0]['id'].'/complete', ['completedAt' => date('Y-m-d', strtotime('+2 days'))]);

        $this->assertStatus(422);
        self::assertArrayHasKey('completedAt', $data['violations']);
    }

    public function testCategoriesCanBeAddedAfterApprovalButNotDeletedWhenInUse(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->approve();
        $this->loginAs($this->pm);

        $data = $this->request('DELETE', '/api/categories/'.$plan['categories'][0]['id']);
        $this->assertStatus(422);
        self::assertSame('category_in_use', $data['error']);

        $plan = $this->request('POST', $this->base.'/categories', ['name' => 'Imprevistos de obra']);
        $this->assertStatus(200);
        self::assertContains('Imprevistos de obra', array_column($plan['categories'], 'name'));

        $data = $this->request('POST', $this->base.'/categories', ['name' => 'MATERIALES']);
        $this->assertStatus(422);
        self::assertArrayHasKey('name', $data['violations']);
    }

    public function testStagesCanBeReorderedAndStarted(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        [$first, $second] = array_column($plan['stages'], 'id');

        $plan = $this->request('PUT', $this->base.'/stages/order', ['ids' => [$second, $first]]);
        self::assertSame(['Estructura', 'Cimentación'], array_column($plan['stages'], 'name'));

        $this->request('PUT', $this->base.'/stages/order', ['ids' => [$second]]);
        $this->assertStatus(422);

        $this->approve();
        $this->loginAs($this->pm);
        $plan = $this->request('POST', "/api/stages/$second/start", ['actualStart' => date('Y-m-d')]);
        self::assertSame('IN_PROGRESS', $plan['stages'][0]['status']);

        $data = $this->request('POST', "/api/stages/$second/start", ['actualStart' => date('Y-m-d')]);
        $this->assertStatus(422);
        self::assertSame('stage_already_started', $data['error']);
    }

    public function testLineRejectsCategoryFromAnotherProject(): void
    {
        $this->loginAs($this->admin);
        $other = $this->createProject('Otro');
        $otherPlan = $this->request('POST', '/api/projects/'.$other->getId().'/categories', ['name' => 'Ajena']);
        $plan = $this->request('POST', $this->base.'/stages', ['name' => 'Cimentación']);

        $data = $this->request('POST', '/api/stages/'.$plan['stages'][0]['id'].'/lines', $this->line($otherPlan['categories'][0]['id']));

        $this->assertStatus(422);
        self::assertArrayHasKey('categoryId', $data['violations']);
    }
}
