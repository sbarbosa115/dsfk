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
 * Deposits, contingency draws, voids, carry-overs and proofs. Budget (BuildsPlan): Cimentación 1,437,506.25 ·
 * Estructura 3,000,000 · contingency 500,000.
 */
final class FinanceApiTest extends ApiTestCase
{
    use BuildsPlan;

    private const TODAY = '2026-10-15';

    private User $lead;
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
        $this->project = $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead]]);
        $this->base = '/api/projects/'.$this->project->getId();

        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        [$this->foundation, $this->structure] = array_column($plan['stages'], 'id');
        $this->categories = array_column($plan['categories'], 'id', 'name');
        $this->loginAs($this->admin);
    }

    public function testDepositsWaitForAnApprovedBudget(): void
    {
        $this->deposit([$this->toStage($this->foundation, '1000')]);
        $this->assertError(409, 'budget_not_approved');

        $finance = $this->json('GET', $this->base.'/finance');
        self::assertFalse($finance['budgetApproved']);
        self::assertFalse($finance['permissions']['deposit']);
    }

    public function testADepositIsSplitAcrossStagePettyCashAndContingency(): void
    {
        $this->approve();

        $movement = $this->deposit([
            $this->toStage($this->foundation, '1500000', $this->categories['Materiales']),
            ['destination' => 'PETTY_CASH', 'amount' => '300000'],
            ['destination' => 'CONTINGENCY', 'amount' => '200000'],
        ], 'TRX-001');

        $this->assertStatus(201);
        self::assertSame('DEPOSIT', $movement['type']);
        self::assertSame('2000000.00', $movement['amount']);
        self::assertSame('TRANSFER', $movement['method']);
        self::assertSame('TRX-001', $movement['reference']);
        self::assertSame('Materiales', $movement['entries'][0]['categoryName']);
        self::assertSame('Cimentación', $movement['entries'][0]['stageName']);
        self::assertSame('Admin', $movement['createdBy']['fullName']);
        self::assertNull($movement['voided']);

        $finance = $this->json('GET', $this->base.'/finance');
        self::assertSame('COP', $finance['currency']);
        self::assertSame('2000000.00', $finance['totals']['deposited']);
        self::assertSame('4937506.25', $finance['totals']['budget']);
        self::assertSame('1500000.00', $finance['totals']['stagesAvailable']);
        self::assertSame('300000.00', $finance['totals']['pettyCash']);
        self::assertSame('200000.00', $finance['totals']['contingency']);
        $stage = $finance['stages'][0];
        self::assertSame('1437506.25', $stage['budget']);
        self::assertSame('1500000.00', $stage['received']);
        self::assertSame('1500000.00', $stage['available']);
        self::assertSame('62493.75', $stage['beyondBudget']);
        self::assertSame(10435, $stage['funded']);
        self::assertSame('Estructura', $stage['nextStage']);
        self::assertSame('500000.00', $finance['contingency']['budgeted']);
        self::assertSame(['Materiales', 'Nómina'], array_column($finance['categories'], 'name'));
        self::assertTrue($finance['permissions']['deposit']);
    }

    public function testOnlyTheAdminMovesMoneyAndTeamLeadsSeeNoFinances(): void
    {
        $this->approve();

        $this->loginAs($this->pm);
        $this->deposit([$this->toStage($this->foundation, '1000')]);
        $this->assertError(403, 'forbidden');
        $this->draw($this->structure, '1');
        $this->assertError(403, 'forbidden');
        $finance = $this->json('GET', $this->base.'/finance');
        self::assertFalse($finance['permissions']['deposit']);
        self::assertFalse($finance['permissions']['void']);

        $this->loginAs($this->lead);
        $this->request('GET', $this->base.'/finance');
        $this->assertError(403, 'forbidden');
        $this->request('GET', $this->base.'/movements');
        $this->assertError(403, 'forbidden');

        $outsider = $this->createUser('otro@example.com');
        $this->loginAs($outsider);
        $this->request('GET', $this->base.'/finance');
        $this->assertError(404, 'project_not_found');
    }

    public function testAnotherProjectsPeopleCannotReachThisProjectsMoneyByItsIds(): void
    {
        $this->approve();
        $deposit = $this->deposit([$this->toStage($this->foundation, '1000')]);
        $movement = $this->upload('/api/movements/'.$deposit['id'].'/attachments', "%PDF-1.4\n%%EOF\n", 'slip.pdf');
        $otherPm = $this->createUser('otro-pm@example.com');
        $other = $this->createProject('Otro', [[$otherPm, ProjectRole::ProjectManager]]);
        $this->loginAs($otherPm);
        $foreignStage = $this->json('POST', '/api/projects/'.$other->getId().'/stages', ['name' => 'Ajena'])['stages'][0]['id'];

        foreach (['/finance', '/movements'] as $path) {
            $this->request('GET', $this->base.$path);
            $this->assertError(404, 'project_not_found');
        }
        $this->request('POST', '/api/movements/'.$deposit['id'].'/void', ['reason' => 'x']);
        $this->assertError(404, 'project_not_found');
        $this->client->request('GET', '/api/attachments/'.$movement['attachments'][0]['id']);
        $this->assertStatus(404);

        // The Admin cannot send this project's contingency to another project's stage either.
        $this->loginAs($this->admin);
        $this->deposit([['destination' => 'CONTINGENCY', 'amount' => '1000']]);
        $data = $this->draw($foreignStage, '10');
        $this->assertStatus(422);
        self::assertArrayHasKey('stageId', $data['violations']);
    }

    public function testDepositValidationNamesTheWrongPart(): void
    {
        $this->approve();
        $this->loginAs($this->pm);
        $other = $this->createProject('Otro', [[$this->pm, ProjectRole::ProjectManager]]);
        $foreignStage = $this->json('POST', '/api/projects/'.$other->getId().'/stages', ['name' => 'Ajena'])['stages'][0]['id'];
        $this->loginAs($this->admin);

        $data = $this->deposit([]);
        $this->assertStatus(422);
        self::assertArrayHasKey('allocations', $data['violations']);

        $data = $this->deposit([$this->toStage($this->foundation, '10'), $this->toStage($foreignStage, '10')]);
        $this->assertStatus(422);
        self::assertArrayHasKey('allocations[1].stageId', $data['violations']);

        $data = $this->deposit([$this->toStage($this->foundation, '-5')]);
        $this->assertStatus(422);
        self::assertArrayHasKey('allocations[0].amount', $data['violations']);

        $data = $this->deposit([$this->toStage($this->foundation, '0')]);
        self::assertArrayHasKey('allocations[0].amount', $data['violations']);

        $data = $this->deposit([$this->toStage($this->foundation, '10', 999999)]);
        self::assertArrayHasKey('allocations[0].categoryId', $data['violations']);

        $data = $this->request('POST', $this->base.'/deposits', ['date' => '2026-10-16', 'method' => 'CASH', 'allocations' => [$this->toStage($this->foundation, '10')]]);
        $this->assertStatus(422);
        self::assertArrayHasKey('date', $data['violations'], 'not in the future');

        $data = $this->request('POST', $this->base.'/deposits', ['date' => self::TODAY, 'method' => 'BITCOIN', 'allocations' => [$this->toStage($this->foundation, '10')]]);
        $this->assertStatus(422);
    }

    public function testAContingencyDrawMovesMoneyToAStage(): void
    {
        $this->approve();
        $this->deposit([['destination' => 'CONTINGENCY', 'amount' => '200000']]);

        $data = $this->draw($this->structure, '250000');
        $this->assertStatus(422);
        self::assertArrayHasKey('amount', $data['violations']);

        $movement = $this->draw($this->structure, '150000');
        $this->assertStatus(201);
        self::assertSame('CONTINGENCY_DRAW', $movement['type']);
        self::assertSame('Sobrecosto de acero', $movement['note']);

        $finance = $this->json('GET', $this->base.'/finance');
        self::assertSame('50000.00', $finance['contingency']['balance']);
        self::assertSame('150000.00', $finance['contingency']['drawn']);
        self::assertSame('150000.00', $finance['stages'][1]['contingencyDraws']);
        self::assertSame('150000.00', $finance['stages'][1]['available']);
    }

    public function testVoidingRemovesAMovementFromTheBalancesButKeepsTheRecord(): void
    {
        $this->approve();
        $deposit = $this->deposit([$this->toStage($this->foundation, '1000000'), ['destination' => 'CONTINGENCY', 'amount' => '200000']]);
        $this->draw($this->structure, '150000');

        // Its contingency money was already drawn.
        $this->request('POST', '/api/movements/'.$deposit['id'].'/void', ['reason' => 'Error de digitación']);
        $this->assertError(409, 'void_would_overdraw');

        $second = $this->deposit([$this->toStage($this->foundation, '500000')], 'DUP-1');
        $this->request('POST', '/api/movements/'.$second['id'].'/void', ['reason' => '']);
        $this->assertStatus(422);
        $voided = $this->json('POST', '/api/movements/'.$second['id'].'/void', ['reason' => 'Depósito duplicado']);
        $this->assertStatus(200);
        self::assertSame('Depósito duplicado', $voided['voided']['reason']);
        self::assertSame('Admin', $voided['voided']['by']);

        $this->request('POST', '/api/movements/'.$second['id'].'/void', ['reason' => 'Otra vez']);
        $this->assertError(409, 'movement_already_voided');

        self::assertSame('1000000.00', $this->json('GET', $this->base.'/finance')['stages'][0]['available']);
        $page = $this->json('GET', $this->base.'/movements');
        self::assertSame(3, $page['total']);
        self::assertSame(['DUP-1'], array_column($this->json('GET', $this->base.'/movements?q=dup')['items'], 'reference'));

        $this->loginAs($this->pm);
        $this->request('POST', '/api/movements/'.$deposit['id'].'/void', ['reason' => 'x']);
        $this->assertError(403, 'forbidden');
    }

    public function testCompletingAStageCarriesItsLeftoverToTheNextStage(): void
    {
        $this->approve();
        $this->deposit([$this->toStage($this->foundation, '1500000', $this->categories['Materiales'])]);
        $uri = "/api/stages/{$this->foundation}/complete";

        $this->request('POST', $uri, ['actualEnd' => self::TODAY]);
        $this->assertError(409, 'stage_not_in_progress');

        $this->loginAs($this->pm);
        $this->request('POST', "/api/stages/{$this->foundation}/start", ['actualStart' => '2026-10-01']);
        $this->assertStatus(200);
        $this->loginAs($this->admin);
        $this->request('POST', $uri, ['actualEnd' => self::TODAY]);
        $this->assertError(409, 'stage_milestones_pending');

        $this->completeMilestones(0);
        $this->loginAs($this->pm);
        $this->request('POST', $uri, ['actualEnd' => self::TODAY]);
        $this->assertError(403, 'forbidden');

        $this->loginAs($this->admin);
        $finance = $this->json('POST', $uri, ['actualEnd' => self::TODAY]);
        $this->assertStatus(200);
        [$foundation, $structure] = $finance['stages'];
        self::assertSame('COMPLETED', $foundation['status']);
        self::assertSame('0.00', $foundation['available']);
        self::assertSame('1500000.00', $foundation['carriedOut']);
        self::assertNull($foundation['nextStage']);
        self::assertSame('1500000.00', $structure['carriedIn']);
        self::assertSame('1500000.00', $structure['available']);

        $movements = $this->json('GET', $this->base.'/movements')['items'];
        self::assertSame('CARRYOVER', $movements[0]['type']);
        $this->request('POST', '/api/movements/'.$movements[0]['id'].'/void', ['reason' => 'x']);
        $this->assertError(409, 'movement_not_voidable');

        // A completed stage no longer receives money.
        $data = $this->deposit([$this->toStage($this->foundation, '10')]);
        self::assertArrayHasKey('allocations[0].stageId', $data['violations']);
        self::assertSame('COMPLETED', $this->json('GET', $this->base.'/plan')['stages'][0]['status']);
    }

    public function testTheLastStagesLeftoverGoesToTheContingency(): void
    {
        $this->approve();
        $this->deposit([$this->toStage($this->structure, '100000')]);
        $this->loginAs($this->pm);
        $this->request('POST', "/api/stages/{$this->structure}/start", ['actualStart' => '2026-10-01']);
        $this->completeMilestones(1);

        $this->loginAs($this->admin);
        $finance = $this->json('POST', "/api/stages/{$this->structure}/complete", ['actualEnd' => self::TODAY]);

        $this->assertStatus(200);
        self::assertSame('100000.00', $finance['contingency']['carriedIn']);
        self::assertSame('100000.00', $finance['contingency']['balance']);
    }

    public function testACategoryHoldingDepositedMoneyCannotBeDeleted(): void
    {
        $this->loginAs($this->pm);
        $plan = $this->json('POST', $this->base.'/categories', ['name' => 'Herramienta']);
        $tools = array_column($plan['categories'], 'id', 'name')['Herramienta'];
        $this->approve();
        $this->deposit([$this->toStage($this->foundation, '1000', $tools)]);
        $this->assertStatus(201);

        $this->request('DELETE', "/api/categories/$tools");
        $this->assertError(409, 'category_in_use');
    }

    public function testADepositToTheCajaMenorOpensItsFirstCycle(): void
    {
        $this->approve();
        $this->deposit([['destination' => 'PETTY_CASH', 'amount' => '300000']]);
        $this->deposit([['destination' => 'PETTY_CASH', 'amount' => '100000']]);

        $cycles = $this->em->getConnection()->fetchAllAssociative('SELECT number, opening_balance, status FROM petty_cash_cycle WHERE project_id = ?', [$this->project->getId()]);
        self::assertSame([['number' => 1, 'opening_balance' => 0, 'status' => 'OPEN']], $cycles);
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM fund_movement WHERE petty_cash_cycle_id IS NOT NULL'));
    }

    public function testAProofOfDepositIsUploadedCheckedAndServedToManagersOnly(): void
    {
        $this->approve();
        $deposit = $this->deposit([$this->toStage($this->foundation, '1000')]);
        $uri = '/api/movements/'.$deposit['id'].'/attachments';

        $movement = $this->upload($uri, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n", 'comprobante.pdf');
        $this->assertStatus(201);
        self::assertSame('comprobante.pdf', $movement['attachments'][0]['name']);
        self::assertSame('application/pdf', $movement['attachments'][0]['mimeType']);

        $data = $this->upload($uri, "<?php echo 'hola';", 'script.pdf');
        $this->assertStatus(422);
        self::assertArrayHasKey('file', $data['violations']);

        $this->client->request('POST', $uri, server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']);
        $this->assertStatus(422);

        $file = '/api/attachments/'.$movement['attachments'][0]['id'];
        $this->loginAs($this->pm);
        $this->upload($uri, "%PDF-1.4\n%%EOF\n", 'otro.pdf');
        $this->assertStatus(403);
        $this->client->request('GET', $file);
        $this->assertStatus(200);
        $headers = $this->client->getResponse()->headers;
        self::assertSame('application/pdf', $headers->get('Content-Type'));
        self::assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        self::assertStringContainsString('inline', (string) $headers->get('Content-Disposition'));

        $this->loginAs($this->lead);
        $this->client->request('GET', $file);
        $this->assertStatus(403);

        $this->loginAs($this->createUser('otro@example.com'));
        $this->client->request('GET', $file);
        $this->assertStatus(404);
    }

    /**
     * @param list<array<string, mixed>> $allocations
     *
     * @return array<string, mixed>
     */
    private function deposit(array $allocations, ?string $reference = null): array
    {
        $data = $this->request('POST', $this->base.'/deposits', [
            'date' => self::TODAY,
            'method' => 'TRANSFER',
            'reference' => $reference,
            'allocations' => $allocations,
        ]);

        return \is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function draw(int $stageId, string $amount): array
    {
        $data = $this->request('POST', $this->base.'/contingency/draws', ['stageId' => $stageId, 'amount' => $amount, 'date' => self::TODAY, 'reason' => 'Sobrecosto de acero']);

        return \is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function toStage(int $stageId, string $amount, ?int $categoryId = null): array
    {
        return ['destination' => 'STAGE', 'stageId' => $stageId, 'amount' => $amount, 'categoryId' => $categoryId];
    }

    private function completeMilestones(int $stageIndex): void
    {
        $this->loginAs($this->pm);
        foreach ($this->json('GET', $this->base.'/plan')['stages'][$stageIndex]['milestones'] as $milestone) {
            $this->request('POST', '/api/milestones/'.$milestone['id'].'/complete', ['completedAt' => self::TODAY]);
            $this->assertStatus(200);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(string $uri, string $content, string $name): array
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $content);
        $this->client->request('POST', $uri, files: ['file' => new UploadedFile($path, $name, null, null, true)], server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);

        return \is_array($data) ? $data : [];
    }
}
