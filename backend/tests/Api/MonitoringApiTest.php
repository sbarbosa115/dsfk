<?php

namespace App\Tests\Api;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectRole;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Email;

/**
 * Phase 4: notifications, dashboard (earned value) and audit log.
 */
class MonitoringApiTest extends ApiTestCase
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

    public function testBudgetWorkflowNotifiesTheOtherSide(): void
    {
        $this->loginAs($this->pm);
        $this->buildPlan();

        $this->request('POST', $this->base.'/budget/submit');
        self::assertSame(['admin@example.com'], $this->recipients());
        self::assertSame('Presupuesto enviado a aprobación: Torre', $this->sentEmails()[0]->getSubject());

        $this->loginAs($this->admin);
        $this->request('POST', $this->base.'/budget/return', ['comment' => 'Revisar acero']);
        self::assertSame(['pm@example.com'], $this->recipients());
        self::assertStringContainsString('Revisar acero', $this->renderedBody());
    }

    public function testExpenseFlowNotificationsAndThresholdAlerts(): void
    {
        [$structure, $materials] = $this->approvedWithFunds();

        // Team Lead submits → PM is told; PM rejects → Team Lead is told.
        $this->loginAs($this->lead);
        $expense = $this->expense($structure, $materials, 'OUT_OF_POCKET', '100000');
        self::assertSame(['pm@example.com'], $this->recipients());
        $this->loginAs($this->pm);
        $this->request('POST', "/api/expenses/{$expense['id']}/reject", ['reason' => 'Sin factura']);
        self::assertSame(['lead@example.com'], $this->recipients());

        // Estructura budget is 3,000,000: 2,500,000 crosses 80%, admin and PM are warned.
        $this->expense($structure, $materials, 'STAGE', '2500000');
        self::assertEqualsCanonicalizing(['admin@example.com', 'pm@example.com'], $this->recipients());
        self::assertSame('Alerta de presupuesto (80%): Torre', $this->sentEmails()[0]->getSubject());

        // Still between 80% and 100%: nothing new.
        $this->expense($structure, $materials, 'STAGE', '100000');
        self::assertSame([], $this->recipients());

        // Crossing 100% (only the highest threshold crossed is sent).
        $this->expense($structure, $materials, 'STAGE', '500000');
        self::assertSame('Alerta de presupuesto (100%): Torre', $this->sentEmails()[0]->getSubject());

        // A failed request sends nothing.
        $this->expense($structure, $materials, 'STAGE', '99999999');
        $this->assertStatus(422);
        self::assertSame([], $this->recipients());
    }

    public function testPettyCashLowAlert(): void
    {
        [$structure, $materials] = $this->approvedWithFunds();
        $this->loginAs($this->pm);

        // Last top-up 300,000; the alert level is 20% = 60,000.
        $this->expense($structure, $materials, 'PETTY_CASH', '200000');
        self::assertSame([], $this->recipients());
        $this->expense($structure, $materials, 'PETTY_CASH', '50000');
        self::assertEqualsCanonicalizing(['admin@example.com', 'pm@example.com'], $this->recipients());
        self::assertSame('Caja menor baja: Torre', $this->sentEmails()[0]->getSubject());
    }

    public function testDailyDigestListsOverdueMilestones(): void
    {
        $this->loginAs($this->pm);
        $this->request('POST', $this->base.'/categories', ['name' => 'Materiales']);
        $plan = $this->request('POST', $this->base.'/stages', ['name' => 'Obra']);
        $stageId = $plan['stages'][0]['id'];
        $this->request('POST', "/api/stages/$stageId/lines", $this->line($plan['categories'][0]['id']));
        $this->request('POST', "/api/stages/$stageId/milestones", ['name' => 'Excavación', 'weight' => '100', 'plannedDate' => date('Y-m-d', strtotime('-3 days'))]);
        $this->assertStatus(200);
        $this->approve();

        $tester = new CommandTester((new Application(self::$kernel))->find('app:alerts:daily'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 project digest(s) queued', $tester->getDisplay());
    }

    public function testEarnedValueDashboard(): void
    {
        $this->loginAs($this->pm);
        $this->request('POST', $this->base.'/categories', ['name' => 'Materiales']);
        $plan = $this->request('POST', $this->base.'/stages', [
            'name' => 'Obra',
            'plannedStart' => date('Y-m-d', strtotime('-30 days')),
            'plannedEnd' => date('Y-m-d', strtotime('+30 days')),
        ]);
        $stageId = $plan['stages'][0]['id'];
        $categoryId = $plan['categories'][0]['id'];
        $this->request('POST', "/api/stages/$stageId/lines", $this->line($categoryId, '1', '1000000'));
        $plan = $this->request('POST', "/api/stages/$stageId/milestones", ['name' => 'A', 'weight' => '50', 'plannedDate' => date('Y-m-d', strtotime('-5 days'))]);
        $this->request('POST', "/api/stages/$stageId/milestones", ['name' => 'B', 'weight' => '50', 'plannedDate' => date('Y-m-d', strtotime('+20 days'))]);
        $milestoneA = $plan['stages'][0]['milestones'][0]['id'];
        $this->approve();

        $this->request('POST', $this->base.'/deposits', ['date' => date('Y-m-d'), 'method' => 'TRANSFER', 'allocations' => [
            ['destination' => 'STAGE', 'stageId' => $stageId, 'amount' => '1000000'],
        ]]);
        $this->loginAs($this->pm);
        $this->expense($stageId, $categoryId, 'STAGE', '400000');
        $this->request('POST', "/api/milestones/$milestoneA/complete", ['completedAt' => date('Y-m-d')]);

        $d = $this->request('GET', $this->base.'/dashboard');
        $this->assertStatus(200);
        self::assertSame(5000, $d['progress']);
        self::assertSame(5000, $d['plannedProgress']);
        self::assertSame('500000.00', $d['earnedValue']);
        self::assertSame('500000.00', $d['plannedValue']);
        self::assertSame('400000.00', $d['totals']['spent']);
        self::assertSame(1.25, $d['cpi']);
        self::assertEquals(1.0, $d['spi']);
        self::assertSame('800000.00', $d['forecastAtCompletion']);
        self::assertSame('200000.00', $d['varianceAtCompletion']);
        self::assertSame('400000.00', $d['monthly'][11]['spent']);
        self::assertSame('1000000.00', $d['monthly'][11]['deposited']);

        $portfolio = $this->request('GET', '/api/dashboard');
        self::assertSame(['Torre'], array_column($portfolio, 'name'));
        self::assertSame(1.25, $portfolio[0]['cpi']);

        $this->loginAs($this->lead);
        $this->request('GET', $this->base.'/dashboard');
        $this->assertStatus(403);
        self::assertSame([], $this->request('GET', '/api/dashboard'));
    }

    public function testAuditLogRecordsWhoChangedWhat(): void
    {
        $this->loginAs($this->admin);
        $this->request('POST', '/api/users', ['email' => 'nuevo@example.com', 'fullName' => 'Nuevo', 'password' => 'secret-password']);
        $this->loginAs($this->pm);
        $this->buildPlan();

        $this->loginAs($this->lead);
        $this->request('GET', '/api/audit');
        $this->assertStatus(403);

        $this->loginAs($this->admin);
        $created = $this->request('GET', '/api/audit?entityType=User')['items'][0];
        self::assertSame('create', $created['action']);
        self::assertSame('Admin', $created['user']);
        self::assertSame(['***', '***'], $created['changes']['password']);

        $contingency = $this->request('GET', '/api/audit?entityType=Budget&projectId='.$this->project->getId())['items'][0];
        self::assertSame('update', $contingency['action']);
        self::assertSame('Pm', $contingency['user']);
        self::assertSame([0, 50000000], $contingency['changes']['contingency']);
    }

    /**
     * Approved plan with Estructura funded (3,200,000) and 300,000 in the caja menor.
     *
     * @return array{int, int} Estructura stage id, Materiales category id
     */
    private function approvedWithFunds(): array
    {
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->approve();
        $structure = $plan['stages'][1]['id'];
        $this->request('POST', $this->base.'/deposits', ['date' => date('Y-m-d'), 'method' => 'TRANSFER', 'allocations' => [
            ['destination' => 'STAGE', 'stageId' => $structure, 'amount' => '3200000'],
            ['destination' => 'PETTY_CASH', 'amount' => '300000'],
        ]]);
        $this->assertStatus(201);

        return [$structure, array_column($plan['categories'], 'id', 'name')['Materiales']];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function expense(int $stageId, int $categoryId, string $paidFrom, string $amount): ?array
    {
        return $this->request('POST', $this->base.'/expenses', [
            'stageId' => $stageId, 'categoryId' => $categoryId, 'date' => date('Y-m-d'),
            'amount' => $amount, 'description' => 'Compra', 'paidFrom' => $paidFrom,
        ]);
    }

    /**
     * @return list<Email>
     */
    private function sentEmails(): array
    {
        return self::getMailerMessages();
    }

    /**
     * @return list<string> recipients of the emails sent by the last request
     */
    private function recipients(): array
    {
        return array_map(static fn (Email $e) => $e->getTo()[0]->getAddress(), $this->sentEmails());
    }

    /** Templated emails are rendered when sent; render the first one to inspect it. */
    private function renderedBody(): string
    {
        $email = $this->sentEmails()[0];
        static::getContainer()->get('twig.mime_body_renderer')->render($email);

        return (string) $email->getHtmlBody();
    }
}
