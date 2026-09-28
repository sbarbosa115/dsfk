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

/**
 * Dashboards. Budget (BuildsPlan): Cimentación 1,437,506.25 (Oct 1 – Dec 15; Excavación 40 % due Oct 20,
 * Vaciado 60 % undated) · Estructura 3,000,000 (Dec 16 – Apr 30) · contingency 500,000.
 */
final class DashboardApiTest extends ApiTestCase
{
    use BuildsPlan;

    private User $lead;
    private Project $project;
    private int $foundation;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::set(new MockClock('2026-10-25 10:00:00', 'America/Bogota'));
        $this->admin = $this->createUser('admin@example.com', admin: true);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->project = $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead]]);
        $this->base = '/api/projects/'.$this->project->getId();
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->foundation = $plan['stages'][0]['id'];
        $this->approve();
    }

    public function testAProjectsHealthComparesProgressWithSpendingAndPlan(): void
    {
        $this->loginAs($this->admin);
        $this->json('POST', $this->base.'/deposits', ['date' => '2026-10-02', 'method' => 'TRANSFER', 'allocations' => [
            ['destination' => 'STAGE', 'stageId' => $this->foundation, 'amount' => '1000000'],
            ['destination' => 'PETTY_CASH', 'amount' => '100000'],
        ]]);
        $this->loginAs($this->pm);
        $categories = array_column($this->json('GET', $this->base.'/plan')['categories'], 'id', 'name');
        $this->json('POST', $this->base.'/expenses', ['stageId' => $this->foundation, 'categoryId' => $categories['Materiales'], 'date' => '2026-10-10', 'amount' => '718753.12', 'description' => 'Concreto', 'paidFrom' => 'STAGE']);
        $this->request('POST', "/api/stages/{$this->foundation}/start", ['actualStart' => '2026-10-01']);
        $milestone = $this->json('GET', $this->base.'/plan')['stages'][0]['milestones'][0]['id'];
        $this->request('POST', "/api/milestones/$milestone/complete", ['completedAt' => '2026-10-20']);

        $d = $this->json('GET', $this->base.'/dashboard');
        $this->assertStatus(200);
        self::assertTrue($d['budgetApproved']);
        self::assertSame('4437506.25', $d['totals']['budget']);
        self::assertSame('500000.00', $d['totals']['contingency']);
        self::assertSame('1100000.00', $d['totals']['deposited']);
        self::assertSame('718753.12', $d['totals']['spent']);
        self::assertSame('381246.88', $d['totals']['available']);
        $stage = $d['stages'][0];
        self::assertSame(4000, $stage['progress']);
        self::assertSame(5000, $stage['executed']);
        self::assertSame('575002.50', $stage['earnedValue'], '40 % of 1,437,506.25');
        self::assertSame(0.8, $stage['cpi']);
        self::assertFalse($stage['delayed']);
        self::assertSame(0.8, $d['cpi']);
        self::assertSame('5546882.77', $d['forecastAtCompletion'], 'BAC × spent / earned');
        self::assertCount(12, $d['monthly']);
        self::assertSame(['month' => '2026-10', 'deposited' => '1100000.00', 'spent' => '718753.12'], $d['monthly'][11]);
        self::assertSame([], $d['alerts'], 'half the stage spent, nothing late or waiting');
    }

    public function testThePortfolioShowsTheProjectsWhoseMoneyThePersonSees(): void
    {
        $this->createProject('Otra');
        $this->loginAs($this->pm);
        self::assertSame(['Torre'], array_column($this->jsonList('GET', '/api/dashboard'), 'name'));

        $this->loginAs($this->admin);
        self::assertSame(['Otra', 'Torre'], array_column($this->jsonList('GET', '/api/dashboard'), 'name'));

        $this->loginAs($this->lead);
        self::assertSame([], $this->jsonList('GET', '/api/dashboard'));
        $this->request('GET', $this->base.'/dashboard');
        $this->assertError(403, 'forbidden');

        $this->loginAs($this->createUser('otro@example.com'));
        $this->request('GET', $this->base.'/dashboard');
        $this->assertError(404, 'project_not_found');
    }

    public function testWhatNeedsAttentionIsListed(): void
    {
        Clock::set(new MockClock('2026-12-20 10:00:00', 'America/Bogota'));
        $this->loginAs($this->lead);
        $categories = array_column($this->json('GET', $this->base.'/plan')['categories'], 'id', 'name');
        $this->json('POST', $this->base.'/expenses', ['stageId' => $this->foundation, 'categoryId' => $categories['Materiales'], 'date' => '2026-12-01', 'amount' => '1000', 'description' => 'Clavos']);

        $this->loginAs($this->pm);
        $alerts = $this->json('GET', $this->base.'/dashboard')['alerts'];
        $byCode = array_column($alerts, null, 'code');
        self::assertSame('Cimentación', $byCode['stage_delayed']['stage']);
        self::assertSame('2026-12-15', $byCode['stage_delayed']['plannedEnd']);
        self::assertSame(1, $byCode['milestones_overdue']['count'], 'Excavación was due Oct 20');
        self::assertSame(1, $byCode['expenses_pending']['count']);
        self::assertSame('info', $byCode['expenses_pending']['level']);
    }
}
