<?php

namespace App\Tests\Api;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectRole;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ExpenseApiTest extends ApiTestCase
{
    use BuildsPlan;

    private User $admin;
    private User $pm;
    private User $lead;
    private User $lead2;
    private Project $project;
    private string $base;
    private int $foundation;
    private int $materials;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin@example.com', admin: true);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->lead2 = $this->createUser('lead2@example.com');
        $this->project = $this->createProject('Torre', [
            [$this->pm, ProjectRole::ProjectManager],
            [$this->lead, ProjectRole::TeamLead],
            [$this->lead2, ProjectRole::TeamLead],
        ]);
        $this->base = '/api/projects/'.$this->project->getId();

        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->foundation = $plan['stages'][0]['id'];
        $this->materials = array_column($plan['categories'], 'id', 'name')['Materiales'];
        $this->approve();

        // Admin funds stage 1 with 1,000,000 and the caja menor with 300,000.
        $this->request('POST', $this->base.'/deposits', ['date' => date('Y-m-d'), 'method' => 'TRANSFER', 'allocations' => [
            ['destination' => 'STAGE', 'stageId' => $this->foundation, 'amount' => '1000000'],
            ['destination' => 'PETTY_CASH', 'amount' => '300000'],
        ]]);
        $this->assertStatus(201);
    }

    public function testProjectManagerExpensesCountImmediatelyAndTakeMoneyFromTheirAccount(): void
    {
        $this->loginAs($this->pm);

        $expense = $this->expense('STAGE', '400000', 'Cemento 80 bultos');
        $this->assertStatus(201);
        self::assertSame('APPROVED', $expense['status']);
        $this->expense('PETTY_CASH', '50000', 'Clavos');
        $this->assertStatus(201);

        $finance = $this->request('GET', $this->base.'/finance');
        $stage = $finance['stages'][0];
        self::assertSame('450000.00', $stage['spent']);
        self::assertSame('600000.00', $stage['available']);
        self::assertSame('987506.25', $stage['remainingBudget']);
        self::assertSame(3130, $stage['executed']);
        self::assertSame('250000.00', $finance['totals']['pettyCash']);
        $materials = array_column($finance['categories'], null, 'name')['Materiales'];
        self::assertSame('450000.00', $materials['spent']);

        $data = $this->expense('STAGE', '600000.01', 'Arena');
        $this->assertStatus(422);
        self::assertSame('insufficient_funds', $data['error']);
        self::assertSame('600000.00', $data['available']);
    }

    public function testEachRolePaysItsOwnWay(): void
    {
        $this->loginAs($this->pm);
        $data = $this->expense('OUT_OF_POCKET', '1000', 'x');
        $this->assertStatus(422);
        self::assertArrayHasKey('paidFrom', $data['violations']);

        $this->loginAs($this->lead);
        $data = $this->expense('STAGE', '1000', 'x');
        $this->assertStatus(422);
        self::assertArrayHasKey('paidFrom', $data['violations']);
    }

    public function testTeamLeadExpenseIsApprovedWithReceiptAndReimbursedFromPettyCash(): void
    {
        $this->loginAs($this->lead);
        $expense = $this->expense('OUT_OF_POCKET', '120000', 'Almuerzos cuadrilla');
        self::assertSame('SUBMITTED', $expense['status']);
        self::assertTrue($expense['permissions']['edit']);
        self::assertFalse($expense['permissions']['approve']);

        $this->request('POST', "/api/expenses/{$expense['id']}/approve");
        $this->assertStatus(403);

        $this->loginAs($this->pm);
        $data = $this->request('POST', "/api/expenses/{$expense['id']}/approve");
        $this->assertStatus(422);
        self::assertSame('receipt_required', $data['error']);
        self::assertSame('0.00', $this->request('GET', $this->base.'/finance')['totals']['spent']);

        $this->loginAs($this->lead);
        $this->uploadReceipt($expense['id']);
        $this->assertStatus(201);

        $this->loginAs($this->pm);
        $approved = $this->request('POST', "/api/expenses/{$expense['id']}/approve");
        self::assertSame('APPROVED', $approved['status']);
        self::assertSame('120000.00', $this->request('GET', $this->base.'/finance')['totals']['spent']);

        $this->loginAs($this->lead);
        $list = $this->request('GET', $this->base.'/expenses');
        self::assertSame('120000.00', $list['summary']['toReimburseTotal']);

        $this->loginAs($this->pm);
        $this->request('POST', $this->base.'/reimbursements', ['expenseIds' => [$expense['id']], 'date' => date('Y-m-d'), 'method' => 'CASH']);
        $this->assertStatus(201);

        $expense = $this->request('GET', "/api/expenses/{$expense['id']}");
        self::assertSame('REIMBURSED', $expense['status']);
        self::assertSame(['CREATED', 'APPROVED', 'REIMBURSED'], array_column($expense['events'], 'type'));
        $finance = $this->request('GET', $this->base.'/finance');
        self::assertSame('180000.00', $finance['totals']['pettyCash']);
        self::assertSame('120000.00', $finance['stages'][0]['spent']);
        // Stage cash is untouched: the money came from the caja menor.
        self::assertSame('1000000.00', $finance['stages'][0]['available']);
    }

    public function testExpensesAboveTheLimitAlsoNeedTheAdmin(): void
    {
        $this->loginAs($this->lead);
        $expense = $this->expense('OUT_OF_POCKET', '600000', 'Alquiler formaleta');
        $this->uploadReceipt($expense['id']);

        $this->loginAs($this->pm);
        $data = $this->request('POST', "/api/expenses/{$expense['id']}/approve");
        self::assertSame('PM_APPROVED', $data['status']);
        $data = $this->request('POST', "/api/expenses/{$expense['id']}/approve");
        self::assertSame('expense_awaiting_admin', $data['error']);
        $this->request('POST', "/api/expenses/{$expense['id']}/reject", ['reason' => 'x']);
        $this->assertStatus(422);

        $this->loginAs($this->admin);
        $data = $this->request('POST', "/api/expenses/{$expense['id']}/approve");
        self::assertSame('APPROVED', $data['status']);
        self::assertSame(['CREATED', 'PM_APPROVED', 'APPROVED'], array_column($data['events'], 'type'));
    }

    public function testRejectedExpenseIsCorrectedAndResubmittedKeepingHistory(): void
    {
        $this->loginAs($this->lead);
        $expense = $this->expense('OUT_OF_POCKET', '90000', 'Transporte');

        $this->loginAs($this->pm);
        $data = $this->request('POST', "/api/expenses/{$expense['id']}/reject", ['reason' => 'Falta la factura del taxi']);
        self::assertSame('REJECTED', $data['status']);
        self::assertSame('Falta la factura del taxi', $data['rejectionReason']);

        $this->loginAs($this->lead2);
        $this->request('PUT', "/api/expenses/{$expense['id']}", $this->payload('OUT_OF_POCKET', '85000', 'Transporte'));
        $this->assertStatus(403);

        $this->loginAs($this->lead);
        $data = $this->request('PUT', "/api/expenses/{$expense['id']}", $this->payload('OUT_OF_POCKET', '85000', 'Transporte obra–ferretería'));
        $this->assertStatus(200);
        self::assertSame('SUBMITTED', $data['status']);
        self::assertNull($data['rejectionReason']);
        $edited = $data['events'][2];
        self::assertSame('EDITED', $edited['type']);
        self::assertSame('90000.00', $edited['previous']['amount']);
        self::assertSame('Transporte', $edited['previous']['description']);
    }

    public function testTeamLeadsOnlySeeTheirOwnExpenses(): void
    {
        $this->loginAs($this->lead);
        $mine = $this->expense('OUT_OF_POCKET', '1000', 'Mío');
        $attachment = $this->uploadReceipt($mine['id'])['attachments'][0]['id'];
        $this->loginAs($this->lead2);
        $this->expense('OUT_OF_POCKET', '2000', 'Del otro');

        self::assertSame(['Del otro'], array_column($this->request('GET', $this->base.'/expenses')['items'], 'description'));
        $this->request('GET', "/api/expenses/{$mine['id']}");
        $this->assertStatus(403);
        $this->client->request('GET', "/api/attachments/$attachment");
        $this->assertStatus(403);

        $this->loginAs($this->lead);
        $this->client->request('GET', "/api/attachments/$attachment");
        $this->assertStatus(200);

        $this->loginAs($this->pm);
        self::assertCount(2, $this->request('GET', $this->base.'/expenses')['items']);
        self::assertCount(2, $this->request('GET', $this->base.'/expenses?status=SUBMITTED&stageId='.$this->foundation)['items']);
        self::assertCount(0, $this->request('GET', $this->base.'/expenses?status=APPROVED')['items']);
    }

    public function testReimbursementNeedsEnoughPettyCash(): void
    {
        $this->loginAs($this->lead);
        $expense = $this->expense('OUT_OF_POCKET', '400000', 'Herramienta');
        $this->uploadReceipt($expense['id']);
        $this->loginAs($this->pm);
        $this->request('POST', "/api/expenses/{$expense['id']}/approve");

        $data = $this->request('POST', $this->base.'/reimbursements', ['expenseIds' => [$expense['id']], 'date' => date('Y-m-d'), 'method' => 'CASH']);

        $this->assertStatus(422);
        self::assertSame('insufficient_funds', $data['error']);
        self::assertSame('300000.00', $data['available']);
    }

    public function testAdminVoidsAnExpenseAndTheMoneyComesBack(): void
    {
        $this->loginAs($this->pm);
        $expense = $this->expense('STAGE', '400000', 'Cemento duplicado');
        $this->request('POST', "/api/expenses/{$expense['id']}/void", ['reason' => 'x']);
        $this->assertStatus(403);

        $this->loginAs($this->admin);
        $data = $this->request('POST', "/api/expenses/{$expense['id']}/void", ['reason' => 'Registrado dos veces']);
        self::assertSame('VOIDED', $data['status']);

        $stage = $this->request('GET', $this->base.'/finance')['stages'][0];
        self::assertSame('0.00', $stage['spent']);
        self::assertSame('1000000.00', $stage['available']);
    }

    public function testPettyCashCyclesAreClosedByThePmAndSignedOffByTheAdmin(): void
    {
        $this->loginAs($this->pm);
        $expense = $this->expense('PETTY_CASH', '280000', 'Compras ferretería');

        $pc = $this->request('GET', $this->base.'/petty-cash');
        self::assertSame(1, $pc['current']['number']);
        self::assertSame('0.00', $pc['current']['openingBalance']);
        self::assertSame('300000.00', $pc['current']['topUps']);
        self::assertSame('280000.00', $pc['current']['spent']);
        self::assertSame('20000.00', $pc['current']['closingBalance']);
        self::assertCount(2, $pc['current']['movements']);

        $closed = $this->request('POST', $this->base.'/petty-cash/close', ['note' => 'Se acabó la caja']);
        self::assertSame('CLOSED', $closed['status']);
        self::assertSame('20000.00', $closed['closingBalance']);

        $pc = $this->request('GET', $this->base.'/petty-cash');
        self::assertSame(2, $pc['current']['number']);
        self::assertSame('20000.00', $pc['current']['openingBalance']);
        self::assertSame(1, $pc['unsignedCount']);
        self::assertFalse($pc['permissions']['signOff']);

        // Closed cycles are frozen.
        $this->loginAs($this->admin);
        $data = $this->request('POST', "/api/expenses/{$expense['id']}/void", ['reason' => 'x']);
        self::assertSame('cycle_closed', $data['error']);

        $this->loginAs($this->pm);
        $this->request('POST', "/api/petty-cash-cycles/{$closed['id']}/sign-off");
        $this->assertStatus(403);
        $this->loginAs($this->admin);
        $signed = $this->request('POST', "/api/petty-cash-cycles/{$closed['id']}/sign-off");
        self::assertSame('SIGNED_OFF', $signed['status']);
        self::assertSame('Admin', $signed['signedOffBy']);

        // A new top-up lands in cycle 2.
        $this->request('POST', $this->base.'/deposits', ['date' => date('Y-m-d'), 'method' => 'CASH', 'allocations' => [['destination' => 'PETTY_CASH', 'amount' => '500000']]]);
        $pc = $this->request('GET', $this->base.'/petty-cash');
        self::assertSame('500000.00', $pc['current']['topUps']);
        self::assertSame('520000.00', $pc['balance']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $paidFrom, string $amount, string $description): array
    {
        return [
            'stageId' => $this->foundation,
            'categoryId' => $this->materials,
            'date' => date('Y-m-d'),
            'amount' => $amount,
            'description' => $description,
            'supplier' => 'Ferretería El Maestro',
            'invoiceNumber' => 'FE-1001',
            'paidFrom' => $paidFrom,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function expense(string $paidFrom, string $amount, string $description): ?array
    {
        return $this->request('POST', $this->base.'/expenses', $this->payload($paidFrom, $amount, $description));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function uploadReceipt(int $expenseId): ?array
    {
        $path = tempnam(sys_get_temp_dir(), 'rcp');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
        $this->client->request('POST', "/api/expenses/$expenseId/attachments", files: ['file' => new UploadedFile($path, 'factura.png', null, null, true)], server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }
}
