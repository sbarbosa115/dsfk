<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Identity\Domain\Model\User;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectRole;
use App\Tests\Functional\ApiTestCase;
use App\Tests\Functional\BuildsPlan;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;

final class PlanApiTest extends ApiTestCase
{
    use BuildsPlan;

    private User $lead;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::set(new MockClock('2026-10-15 10:00:00', 'America/Bogota'));
        $this->admin = $this->createUser('admin@example.com', admin: true);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->project = $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead]]);
        $this->base = '/api/projects/'.$this->project->getId();
    }

    public function testAProjectManagerBuildsTheBudgetAndTheServerDoesTheArithmetic(): void
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
        self::assertSame('2026-10-20', $foundation['milestones'][0]['plannedDate']);
        self::assertSame('4437506.25', $plan['budget']['stagesTotal']);
        self::assertSame('500000.00', $plan['budget']['contingency']);
        self::assertSame('4937506.25', $plan['budget']['total']);
        self::assertSame(3239, $foundation['weight'], '1,437,506.25 / 4,437,506.25 ≈ 32.39 %');
        self::assertEqualsCanonicalizing(['3437506.25', '1000000.00'], array_column($plan['budget']['byCategory'], 'total'));
        self::assertTrue($plan['permissions']['edit']);
        self::assertTrue($plan['permissions']['submit']);
        self::assertFalse($plan['permissions']['review']);
    }

    public function testATeamLeadSeesThePlanWithoutMoneyAndCannotEditIt(): void
    {
        $this->loginAs($this->pm);
        $this->buildPlan();
        $this->loginAs($this->lead);

        $plan = $this->json('GET', $this->base.'/plan');

        $this->assertStatus(200);
        self::assertNull($plan['budget']);
        self::assertNull($plan['stages'][0]['lines']);
        self::assertNull($plan['stages'][0]['budgetTotal']);
        self::assertNull($plan['issues']);
        self::assertCount(2, $plan['stages'][0]['milestones']);
        self::assertFalse($plan['permissions']['edit']);

        $this->request('POST', $this->base.'/stages', ['name' => 'Acabados']);
        $this->assertError(403, 'forbidden');
        $this->request('POST', '/api/stages/'.$plan['stages'][0]['id'].'/milestones', ['name' => 'X', 'weight' => '1']);
        $this->assertError(403, 'forbidden');
    }

    public function testOutsidersDoNotLearnThePlanOrItsPartsExist(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->loginAs($this->createUser('other@example.com'));

        $this->request('GET', $this->base.'/plan');
        $this->assertError(404, 'project_not_found');
        $this->request('PATCH', '/api/stages/'.$plan['stages'][0]['id'], ['name' => 'Hackeada']);
        $this->assertError(404, 'project_not_found');
        $this->request('DELETE', '/api/budget-lines/'.$plan['stages'][0]['lines'][0]['id']);
        $this->assertError(404, 'project_not_found');
        $this->request('POST', '/api/milestones/'.$plan['stages'][0]['milestones'][0]['id'].'/complete', ['completedAt' => '2026-10-15']);
        $this->assertError(404, 'project_not_found');
        $this->request('DELETE', '/api/categories/'.$plan['categories'][0]['id']);
        $this->assertError(404, 'project_not_found');
        $this->request('PATCH', '/api/stages/999999', ['name' => 'X']);
        $this->assertError(404, 'stage_not_found');
    }

    public function testAnIncompleteBudgetCannotBeSubmittedAndTheIssuesSayWhere(): void
    {
        $this->loginAs($this->pm);
        $stageId = $this->json('POST', $this->base.'/stages', ['name' => 'Cimentación'])['stages'][0]['id'];

        $data = $this->json('POST', $this->base.'/budget/submit');

        $this->assertStatus(422);
        self::assertSame('budget_incomplete', $data['error']);
        self::assertEqualsCanonicalizing(
            [['code' => 'stage_without_lines', 'stageId' => $stageId], ['code' => 'milestone_weights', 'stageId' => $stageId]],
            $data['issues'],
        );
    }

    public function testMilestoneWeightsOfAStageCannotPassOneHundredPercent(): void
    {
        $this->loginAs($this->pm);
        $stageId = $this->json('POST', $this->base.'/stages', ['name' => 'Cimentación'])['stages'][0]['id'];
        $this->json('POST', "/api/stages/$stageId/milestones", ['name' => 'Excavación', 'weight' => '60']);

        $data = $this->json('POST', "/api/stages/$stageId/milestones", ['name' => 'Vaciado', 'weight' => '40.01']);

        $this->assertStatus(422);
        self::assertArrayHasKey('weight', $data['violations']);
    }

    public function testTheApprovalWorkflowLocksTheBudgetForEveryone(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $lineId = $plan['stages'][0]['lines'][0]['id'];

        $this->request('POST', $this->base.'/budget/submit');
        $this->assertStatus(200);

        $this->request('DELETE', "/api/budget-lines/$lineId");
        $this->assertError(409, 'budget_locked');
        $this->request('POST', $this->base.'/budget/approve');
        $this->assertError(403, 'forbidden');

        $this->loginAs($this->admin);
        $data = $this->json('POST', $this->base.'/budget/return', ['comment' => '']);
        $this->assertStatus(422);
        self::assertArrayHasKey('comment', $data['violations']);
        $this->request('POST', $this->base.'/budget/return', ['comment' => 'Revisar precio del concreto']);
        $this->assertStatus(200);

        $this->loginAs($this->pm);
        $this->request('PUT', "/api/budget-lines/$lineId", $this->line($plan['categories'][0]['id'], unitPrice: '36000'));
        $this->assertStatus(200);
        $this->request('POST', $this->base.'/budget/submit');

        $this->loginAs($this->admin);
        $plan = $this->json('POST', $this->base.'/budget/approve');
        $this->assertStatus(200);
        self::assertSame('APPROVED', $plan['budgetStatus']);
        self::assertSame(['SUBMITTED', 'RETURNED', 'SUBMITTED', 'APPROVED'], array_column($plan['budget']['events'], 'status'));
        self::assertSame('Revisar precio del concreto', $plan['budget']['events'][1]['comment']);
        self::assertSame('Admin', $plan['budget']['events'][3]['user']['fullName']);
        self::assertSame('ACTIVE', $this->json('GET', $this->base)['status']);

        $this->request('PUT', $this->base.'/budget/contingency', ['contingency' => '1']);
        $this->assertError(409, 'budget_locked');
        $this->request('POST', $this->base.'/stages', ['name' => 'Nueva']);
        $this->assertError(409, 'budget_locked');
    }

    public function testAfterApprovalStageNamesCanBeFixedButNotTheirDates(): void
    {
        $this->loginAs($this->pm);
        $stageId = $this->buildPlan()['stages'][0]['id'];
        $this->approve();
        $this->loginAs($this->pm);

        $plan = $this->json('PATCH', "/api/stages/$stageId", ['name' => 'Cimentación profunda']);
        $this->assertStatus(200);
        self::assertSame('Cimentación profunda', $plan['stages'][0]['name']);

        $this->request('PATCH', "/api/stages/$stageId", ['plannedEnd' => '2027-01-31']);
        $this->assertError(409, 'budget_locked');
    }

    public function testMilestonesAreMetOnlyAfterApprovalAndDriveProgress(): void
    {
        $this->loginAs($this->pm);
        $milestoneId = $this->buildPlan()['stages'][0]['milestones'][0]['id'];

        $this->request('POST', "/api/milestones/$milestoneId/complete", ['completedAt' => '2026-10-15']);
        $this->assertError(409, 'budget_not_approved');

        $this->approve();
        $this->loginAs($this->pm);

        $data = $this->json('POST', "/api/milestones/$milestoneId/complete", ['completedAt' => '2026-10-16']);
        $this->assertStatus(422);
        self::assertArrayHasKey('completedAt', $data['violations'], 'not in the future');

        $plan = $this->json('POST', "/api/milestones/$milestoneId/complete", ['completedAt' => '2026-10-15', 'notes' => 'Excavación terminada']);
        $this->assertStatus(200);
        $stage = $plan['stages'][0];
        self::assertSame(4000, $stage['progress']);
        self::assertSame('Pm', $stage['milestones'][0]['completedBy']['fullName']);
        self::assertSame('Excavación terminada', $stage['milestones'][0]['completionNotes']);
        self::assertSame(1295, $plan['progress'], '40 % × 1,437,506.25 / 4,437,506.25');

        $this->request('POST', "/api/milestones/$milestoneId/complete", ['completedAt' => '2026-10-15']);
        $this->assertError(409, 'milestone_already_completed');
        $this->request('POST', "/api/milestones/$milestoneId/reopen");
        $this->assertError(403, 'forbidden');

        $this->loginAs($this->admin);
        self::assertSame(0, $this->json('POST', "/api/milestones/$milestoneId/reopen")['stages'][0]['progress']);
    }

    public function testAMilestoneWhosePlannedDatePassedIsOverdue(): void
    {
        $this->loginAs($this->pm);
        $stageId = $this->json('POST', $this->base.'/stages', ['name' => 'Cimentación'])['stages'][0]['id'];

        $plan = $this->json('POST', "/api/stages/$stageId/milestones", ['name' => 'Excavación', 'weight' => '50', 'plannedDate' => '2026-10-14']);
        $plan = $this->json('POST', "/api/stages/$stageId/milestones", ['name' => 'Vaciado', 'weight' => '50', 'plannedDate' => '2026-10-15']);

        self::assertSame([true, false], array_column($plan['stages'][0]['milestones'], 'overdue'));
    }

    public function testCategoriesAreAddedAfterApprovalButOnlyUnusedOnesDeleted(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->approve();
        $this->loginAs($this->pm);

        $this->request('DELETE', '/api/categories/'.$plan['categories'][0]['id']);
        $this->assertError(409, 'category_in_use');

        $plan = $this->json('POST', $this->base.'/categories', ['name' => 'Imprevistos de obra']);
        $this->assertStatus(200);
        self::assertContains('Imprevistos de obra', array_column($plan['categories'], 'name'));
        $spare = array_column($plan['categories'], 'id', 'name')['Imprevistos de obra'];

        $data = $this->json('POST', $this->base.'/categories', ['name' => 'MATERIALES']);
        $this->assertStatus(422);
        // Written in English in the domain, sent in Spanish (translations/validators.es.yaml).
        self::assertSame(['Ya existe una categoría con ese nombre.'], $data['violations']['name']);

        $plan = $this->json('PATCH', "/api/categories/$spare", ['name' => 'Imprevistos']);
        self::assertContains('Imprevistos', array_column($plan['categories'], 'name'));
        $plan = $this->json('DELETE', "/api/categories/$spare");
        $this->assertStatus(200);
        self::assertNotContains('Imprevistos', array_column($plan['categories'], 'name'));
    }

    public function testStagesAreReorderedDeletedAndStarted(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        [$first, $second] = array_column($plan['stages'], 'id');

        $plan = $this->json('PUT', $this->base.'/stages/order', ['ids' => [$second, $first]]);
        self::assertSame(['Estructura', 'Cimentación'], array_column($plan['stages'], 'name'));
        $this->request('PUT', $this->base.'/stages/order', ['ids' => [$second]]);
        $this->assertStatus(422);

        $extra = $this->json('POST', $this->base.'/stages', ['name' => 'Temporal'])['stages'][2]['id'];
        self::assertCount(2, $this->json('DELETE', "/api/stages/$extra")['stages']);

        $this->request('POST', "/api/stages/$second/start", ['actualStart' => '2026-10-15']);
        $this->assertError(409, 'budget_not_approved');
        $this->approve();
        $this->loginAs($this->pm);
        $plan = $this->json('POST', "/api/stages/$second/start", ['actualStart' => '2026-10-15']);
        self::assertSame('IN_PROGRESS', $plan['stages'][0]['status']);
        self::assertSame('2026-10-15', $plan['stages'][0]['actualStart']);
        $this->request('POST', "/api/stages/$second/start", ['actualStart' => '2026-10-15']);
        $this->assertError(409, 'stage_already_started');
    }

    public function testALineCannotUseAnotherProjectsCategory(): void
    {
        $this->loginAs($this->admin);
        $other = $this->createProject('Otro');
        $foreign = $this->json('POST', '/api/projects/'.$other->getId().'/categories', ['name' => 'Ajena'])['categories'][0]['id'];
        $stageId = $this->json('POST', $this->base.'/stages', ['name' => 'Cimentación'])['stages'][0]['id'];

        $data = $this->json('POST', "/api/stages/$stageId/lines", $this->line($foreign));

        $this->assertStatus(422);
        self::assertArrayHasKey('categoryId', $data['violations']);
    }

    public function testAmountsAreCheckedAgainstTheProjectCurrency(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->json('POST', $this->base.'/categories', ['name' => 'Materiales']);
        $stageId = $this->json('POST', $this->base.'/stages', ['name' => 'Cimentación'])['stages'][0]['id'];

        $data = $this->json('POST', "/api/stages/$stageId/lines", ['categoryId' => $plan['categories'][0]['id'], 'description' => 'x', 'unit' => 'm', 'quantity' => '1.2345', 'unitPrice' => '-3']);

        $this->assertStatus(422);
        self::assertEqualsCanonicalizing(['quantity', 'unitPrice'], array_keys($data['violations']));
    }

    public function testAProjectManagerCannotReachAnotherProjectsPlanThroughItsIds(): void
    {
        $this->loginAs($this->pm);
        $mine = $this->buildPlan();
        $otherPm = $this->createUser('pm2@example.com');
        $other = $this->createProject('Otro', [[$otherPm, ProjectRole::ProjectManager]]);
        $this->loginAs($otherPm);
        $theirs = $this->json('POST', '/api/projects/'.$other->getId().'/stages', ['name' => 'Suya'])['stages'][0]['id'];

        $this->loginAs($this->pm);
        $this->request('PATCH', "/api/stages/$theirs", ['name' => 'Mía']);
        $this->assertError(404, 'project_not_found');
        $this->request('PUT', $this->base.'/stages/order', ['ids' => [$theirs, $mine['stages'][0]['id']]]);
        $this->assertStatus(422);

        $this->loginAs($otherPm);
        self::assertSame('Suya', $this->json('GET', '/api/projects/'.$other->getId().'/plan')['stages'][0]['name']);
    }
}
