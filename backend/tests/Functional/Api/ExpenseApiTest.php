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
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Expenses and reimbursements. Budget (BuildsPlan): Cimentación 1,437,506.25 · Estructura 3,000,000; the Team
 * Lead limit is the default 500,000.
 */
final class ExpenseApiTest extends ApiTestCase
{
    use BuildsPlan;

    private const TODAY = '2026-10-15';

    private User $lead;
    private User $otherLead;
    private Project $project;
    private int $foundation;
    private int $structure;
    /** @var array<string, int> */
    private array $categories;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::set(new MockClock(self::TODAY.' 10:00:00', 'America/Bogota'));
        $this->admin = $this->createUser('admin@example.com', admin: true);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->otherLead = $this->createUser('lead2@example.com');
        $this->project = $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead], [$this->otherLead, ProjectRole::TeamLead]]);
        $this->base = '/api/projects/'.$this->project->getId();

        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        [$this->foundation, $this->structure] = array_column($plan['stages'], 'id');
        $this->categories = array_column($plan['categories'], 'id', 'name');
    }

    public function testExpensesWaitForAnApprovedBudget(): void
    {
        $this->loginAs($this->lead);
        $this->request('POST', $this->base.'/expenses', $this->expense('1000'));
        $this->assertError(409, 'budget_not_approved');
    }

    public function testThePmPaysFromAStageAndItCountsAtOnceWithinTheMoneyThere(): void
    {
        $this->fund();
        $this->loginAs($this->pm);

        $data = $this->request('POST', $this->base.'/expenses', $this->expense('1000', paidFrom: 'STAGE', stageId: $this->structure));
        $this->assertError(422, 'insufficient_funds');
        self::assertIsArray($data);
        self::assertSame('0.00', $data['available']);
        // The translation fills in its placeholder, which never leaves the server.
        self::assertSame(['Fondos insuficientes. Disponible: $ 0.'], $data['violations']['amount']);
        self::assertArrayNotHasKey('violationParameters', $data);

        $expense = $this->json('POST', $this->base.'/expenses', $this->expense('300000.50', paidFrom: 'STAGE'));
        $this->assertStatus(201);
        self::assertSame('APPROVED', $expense['status']);
        self::assertSame('Cimentación', $expense['stage']['name']);
        self::assertSame('Materiales', $expense['category']['name']);
        self::assertSame('Pm', $expense['paidBy']['name']);
        self::assertSame(['CREATED'], array_column($expense['events'], 'type'));

        $finance = $this->json('GET', $this->base.'/finance');
        self::assertSame('300000.50', $finance['totals']['spent']);
        self::assertSame('1199999.50', $finance['stages'][0]['available']);
        self::assertSame('300000.50', $finance['stages'][0]['spent']);
        self::assertSame(2087, $finance['stages'][0]['executed']);
        self::assertSame('300000.50', $finance['categories'][0]['spent']);

        $this->request('POST', $this->base.'/expenses', $this->expense('10', paidFrom: null));
        $this->assertStatus(422);
        $this->request('POST', $this->base.'/expenses', $this->expense('10', paidFrom: 'OUT_OF_POCKET'));
        $this->assertStatus(422);
    }

    public function testATeamLeadsExpenseNeedsAReceiptThePmsApprovalAndAnAdminsAboveTheLimit(): void
    {
        $this->fund();
        $this->loginAs($this->lead);
        $this->request('POST', $this->base.'/expenses', $this->expense('10', paidFrom: 'STAGE'));
        $this->assertStatus(422);

        $small = $this->json('POST', $this->base.'/expenses', $this->expense('120000'));
        $this->assertStatus(201);
        self::assertSame('SUBMITTED', $small['status']);
        self::assertSame('OUT_OF_POCKET', $small['paidFrom']);
        self::assertTrue($small['permissions']['edit']);
        self::assertFalse($small['permissions']['approve']);
        $big = $this->json('POST', $this->base.'/expenses', $this->expense('600000'));

        $this->request('POST', "/api/expenses/{$small['id']}/approve");
        $this->assertError(403, 'forbidden');
        $this->loginAs($this->pm);
        $this->request('POST', "/api/expenses/{$small['id']}/approve");
        $this->assertError(409, 'receipt_required');

        $this->loginAs($this->lead);
        foreach ([$small, $big] as $expense) {
            $withReceipt = $this->upload("/api/expenses/{$expense['id']}/attachments", 'factura.pdf');
            $this->assertStatus(201);
            self::assertSame('factura.pdf', $withReceipt['attachments'][0]['name']);
        }

        $this->loginAs($this->pm);
        self::assertSame('APPROVED', $this->json('POST', "/api/expenses/{$small['id']}/approve")['status']);
        $big = $this->json('POST', "/api/expenses/{$big['id']}/approve");
        self::assertSame('PM_APPROVED', $big['status']);
        self::assertFalse($big['permissions']['approve']);
        $this->request('POST', "/api/expenses/{$big['id']}/approve");
        $this->assertError(409, 'expense_awaiting_admin');

        $this->loginAs($this->admin);
        $big = $this->json('POST', "/api/expenses/{$big['id']}/approve");
        self::assertSame('APPROVED', $big['status']);
        self::assertSame(['CREATED', 'PM_APPROVED', 'APPROVED'], array_column($big['events'], 'type'));
        self::assertTrue($big['permissions']['reimburse']);

        $this->loginAs($this->pm);
        $page = $this->json('GET', $this->base.'/expenses');
        self::assertSame(2, $page['total']);
        self::assertSame(2, $page['summary']['toReimburseCount']);
        self::assertSame('720000.00', $page['summary']['toReimburseTotal']);
        self::assertSame('500000.00', $page['summary']['teamLeadLimit']);
        self::assertSame('720000.00', $this->json('GET', $this->base.'/finance')['totals']['spent'], 'approved Team Lead expenses count against the budget');
    }

    public function testARejectedExpenseIsCorrectedByItsTeamLeadWithTheOldValuesKept(): void
    {
        $this->fund();
        $this->loginAs($this->lead);
        $expense = $this->json('POST', $this->base.'/expenses', $this->expense('120000'));

        $this->loginAs($this->pm);
        $this->request('POST', "/api/expenses/{$expense['id']}/reject", ['reason' => '']);
        $this->assertStatus(422);
        $rejected = $this->json('POST', "/api/expenses/{$expense['id']}/reject", ['reason' => 'Falta la factura']);
        self::assertSame('REJECTED', $rejected['status']);
        self::assertSame('Falta la factura', $rejected['rejectionReason']);
        $this->request('PUT', "/api/expenses/{$expense['id']}", $this->expense('90000'));
        $this->assertError(403, 'forbidden');

        $this->loginAs($this->otherLead);
        $this->request('GET', "/api/expenses/{$expense['id']}");
        $this->assertError(404, 'expense_not_found');
        self::assertSame(0, $this->json('GET', $this->base.'/expenses')['total']);

        $this->loginAs($this->lead);
        $corrected = $this->json('PUT', "/api/expenses/{$expense['id']}", $this->expense('90000', stageId: $this->structure, description: 'Cemento gris'));
        $this->assertStatus(200);
        self::assertSame('SUBMITTED', $corrected['status']);
        self::assertNull($corrected['rejectionReason']);
        self::assertSame('Estructura', $corrected['stage']['name']);
        $edited = $corrected['events'][2];
        self::assertSame('EDITED', $edited['type']);
        self::assertSame(['stage' => 'Cimentación', 'category' => 'Materiales', 'date' => self::TODAY, 'amount' => '120000.00', 'description' => 'Cemento', 'supplier' => 'Ferretería El Tornillo', 'invoiceNumber' => 'F-100'], $edited['previous']);
    }

    public function testApprovedTeamLeadExpensesArePaidBackFromTheCajaMenor(): void
    {
        $this->fund();
        $first = $this->approvedTeamLeadExpense('120000');
        $second = $this->approvedTeamLeadExpense('50000');
        $this->loginAs($this->lead);
        $pending = $this->json('POST', $this->base.'/expenses', $this->expense('10'));

        $this->loginAs($this->pm);
        $data = $this->request('POST', $this->base.'/reimbursements', $this->payBack([$first, $pending['id']]));
        $this->assertStatus(422);
        self::assertIsArray($data);
        self::assertArrayHasKey('expenseIds', $data['violations']);
        $data = $this->request('POST', $this->base.'/reimbursements', $this->payBack([999999]));
        $this->assertStatus(422);

        $this->request('POST', $this->base.'/reimbursements', $this->payBack([$first, $second]));
        $this->assertStatus(201);
        $paid = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsList($paid);
        self::assertIsArray($paid[0]);
        self::assertSame(['REIMBURSED', 'REIMBURSED'], array_column($paid, 'status'));
        self::assertSame(self::TODAY, $paid[0]['reimbursement']['date']);
        self::assertSame('CASH', $paid[0]['reimbursement']['method']);

        $finance = $this->json('GET', $this->base.'/finance');
        self::assertSame('130000.00', $finance['totals']['pettyCash'], '300,000 − 170,000');
        self::assertSame('170000.00', $finance['totals']['spent']);

        $this->request('POST', $this->base.'/reimbursements', $this->payBack([$first]));
        $this->assertStatus(422);

        $this->loginAs($this->admin);
        $this->request('POST', "/api/expenses/$first/void", ['reason' => 'x']);
        $this->assertError(409, 'expense_invalid_status');
    }

    public function testTheCajaMenorMustHoldWhatIsPaidBack(): void
    {
        $this->approve();
        $this->loginAs($this->admin);
        $this->json('POST', $this->base.'/deposits', ['date' => self::TODAY, 'method' => 'CASH', 'allocations' => [['destination' => 'PETTY_CASH', 'amount' => '100000']]]);
        $expense = $this->approvedTeamLeadExpense('120000');

        $this->loginAs($this->pm);
        $data = $this->request('POST', $this->base.'/reimbursements', $this->payBack([$expense]));
        $this->assertError(422, 'insufficient_funds');
        self::assertIsArray($data);
        self::assertSame('100000.00', $data['available']);
    }

    public function testAnAdminVoidsAnApprovedExpenseAndItsMoneyGoesBack(): void
    {
        $this->fund();
        $this->loginAs($this->pm);
        $expense = $this->json('POST', $this->base.'/expenses', $this->expense('200000', paidFrom: 'STAGE'));

        $this->request('POST', "/api/expenses/{$expense['id']}/void", ['reason' => 'x']);
        $this->assertError(403, 'forbidden');

        $this->loginAs($this->admin);
        $voided = $this->json('POST', "/api/expenses/{$expense['id']}/void", ['reason' => 'Duplicado']);
        self::assertSame('VOIDED', $voided['status']);
        self::assertSame('Duplicado', $voided['events'][1]['comment']);
        $finance = $this->json('GET', $this->base.'/finance');
        self::assertSame('1500000.00', $finance['stages'][0]['available']);
        self::assertSame('0.00', $finance['totals']['spent']);
    }

    public function testReceiptsAreOpenedByTheirTeamLeadAndTheManagersOnly(): void
    {
        $this->fund();
        $this->loginAs($this->lead);
        $expense = $this->json('POST', $this->base.'/expenses', $this->expense('1000'));
        $file = $this->upload("/api/expenses/{$expense['id']}/attachments", 'factura.pdf')['attachments'][0]['id'];

        foreach ([[$this->lead, 200], [$this->pm, 200], [$this->otherLead, 403]] as [$user, $status]) {
            $this->loginAs($user);
            $this->client->request('GET', "/api/attachments/$file");
            $this->assertStatus($status);
        }

        $this->loginAs($this->otherLead);
        $this->upload("/api/expenses/{$expense['id']}/attachments", 'otra.pdf');
        $this->assertStatus(404);
    }

    public function testACategoryAnExpenseUsesStays(): void
    {
        $this->loginAs($this->pm);
        $tools = array_column($this->json('POST', $this->base.'/categories', ['name' => 'Herramienta'])['categories'], 'id', 'name')['Herramienta'];
        $this->fund();
        $this->loginAs($this->lead);
        $this->json('POST', $this->base.'/expenses', ['categoryId' => $tools] + $this->expense('1000'));

        $this->loginAs($this->pm);
        $this->request('DELETE', "/api/categories/$tools");
        $this->assertError(409, 'category_in_use');
    }

    public function testAnotherProjectsExpensesCannotBePaidBackFromThisCajaMenor(): void
    {
        $this->fund();
        $expense = $this->approvedTeamLeadExpense('1000');
        $otherPm = $this->createUser('otro-pm@example.com');
        $other = $this->createProject('Otro', [[$otherPm, ProjectRole::ProjectManager]]);
        $this->loginAs($otherPm);

        $data = $this->request('POST', '/api/projects/'.$other->getId().'/reimbursements', $this->payBack([$expense]));
        $this->assertStatus(422);
        self::assertIsArray($data);
        self::assertArrayHasKey('expenseIds', $data['violations']);
    }

    public function testATeamLeadNoLongerChangesAnApprovedExpense(): void
    {
        $this->fund();
        $expense = $this->approvedTeamLeadExpense('1000');
        $this->loginAs($this->lead);

        $this->upload("/api/expenses/$expense/attachments", 'otra.pdf');
        $this->assertError(403, 'forbidden');
        $this->request('PUT', "/api/expenses/$expense", $this->expense('2000'));
        $this->assertError(409, 'expense_not_editable');
        $this->request('POST', "/api/expenses/$expense/approve");
        $this->assertError(403, 'forbidden');
        $this->request('POST', $this->base.'/reimbursements', $this->payBack([$expense]));
        $this->assertError(403, 'forbidden');
    }

    public function testOutsidersDoNotReachAProjectsExpenses(): void
    {
        $this->fund();
        $this->loginAs($this->lead);
        $expense = $this->json('POST', $this->base.'/expenses', $this->expense('1000'));
        $outsider = $this->createUser('otro@example.com');
        $this->loginAs($outsider);

        $this->request('GET', $this->base.'/expenses');
        $this->assertError(404, 'project_not_found');
        foreach (['GET' => "/api/expenses/{$expense['id']}", 'POST' => "/api/expenses/{$expense['id']}/approve"] as $method => $uri) {
            $this->request($method, $uri);
            $this->assertError(404, 'project_not_found');
        }
    }

    private function fund(): void
    {
        $this->approve();
        $this->loginAs($this->admin);
        $this->json('POST', $this->base.'/deposits', ['date' => self::TODAY, 'method' => 'TRANSFER', 'allocations' => [
            ['destination' => 'STAGE', 'stageId' => $this->foundation, 'amount' => '1500000'],
            ['destination' => 'PETTY_CASH', 'amount' => '300000'],
        ]]);
        $this->assertStatus(201);
    }

    private function approvedTeamLeadExpense(string $amount): int
    {
        $this->loginAs($this->lead);
        $expense = $this->json('POST', $this->base.'/expenses', $this->expense($amount));
        $this->upload("/api/expenses/{$expense['id']}/attachments", 'factura.pdf');
        $this->loginAs($this->pm);
        $this->json('POST', "/api/expenses/{$expense['id']}/approve");
        $this->assertStatus(200);

        return $expense['id'];
    }

    /**
     * @param list<int> $ids
     *
     * @return array<string, mixed>
     */
    private function payBack(array $ids): array
    {
        return ['expenseIds' => $ids, 'date' => self::TODAY, 'method' => 'CASH', 'reference' => null];
    }

    /**
     * @return array<string, mixed>
     */
    private function expense(string $amount, ?string $paidFrom = 'OUT_OF_POCKET', ?int $stageId = null, string $description = 'Cemento'): array
    {
        return [
            'stageId' => $stageId ?? $this->foundation,
            'categoryId' => $this->categories['Materiales'],
            'date' => self::TODAY,
            'amount' => $amount,
            'description' => $description,
            'supplier' => 'Ferretería El Tornillo',
            'invoiceNumber' => 'F-100',
            'paidFrom' => $paidFrom,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(string $uri, string $name): array
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $this->client->request('POST', $uri, files: ['file' => new UploadedFile($path, $name, null, null, true)], server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);

        return \is_array($data) ? $data : [];
    }
}
