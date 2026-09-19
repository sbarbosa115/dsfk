<?php

namespace App\Tests\Api;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectRole;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FinanceApiTest extends ApiTestCase
{
    use BuildsPlan;

    private User $admin;
    private User $pm;
    private User $lead;
    private Project $project;
    private string $base;
    /** @var array<string, mixed> */
    private array $plan;
    private int $foundation;
    private int $structure;
    private int $materials;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin@example.com', admin: true);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->project = $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead]]);
        $this->base = '/api/projects/'.$this->project->getId();

        $this->loginAs($this->pm);
        $this->plan = $this->buildPlan();
        [$this->foundation, $this->structure] = array_column($this->plan['stages'], 'id');
        $this->materials = array_column($this->plan['categories'], 'id', 'name')['Materiales'];
    }

    public function testDepositRequiresAnApprovedBudget(): void
    {
        $this->loginAs($this->admin);

        $data = $this->deposit([$this->toStage($this->foundation, '1000')]);

        $this->assertStatus(422);
        self::assertSame('budget_not_approved', $data['error']);
    }

    public function testDepositIsSplitAcrossStagePettyCashAndContingency(): void
    {
        $this->approve();

        $movement = $this->deposit([
            $this->toStage($this->foundation, '1500000', $this->materials),
            ['destination' => 'PETTY_CASH', 'amount' => '300000'],
            ['destination' => 'CONTINGENCY', 'amount' => '200000'],
        ], reference: 'TRX-001');

        $this->assertStatus(201);
        self::assertSame('2000000.00', $movement['amount']);
        self::assertSame('TRANSFER', $movement['method']);
        self::assertSame('TRX-001', $movement['reference']);
        self::assertCount(3, $movement['entries']);
        self::assertSame('Materiales', $movement['entries'][0]['categoryName']);

        $finance = $this->request('GET', $this->base.'/finance');
        self::assertSame('2000000.00', $finance['totals']['deposited']);
        self::assertSame('1500000.00', $finance['totals']['stagesAvailable']);
        self::assertSame('300000.00', $finance['totals']['pettyCash']);
        self::assertSame('200000.00', $finance['totals']['contingency']);

        $stage = $finance['stages'][0];
        self::assertSame('1437506.25', $stage['budget']);
        self::assertSame('1500000.00', $stage['received']);
        self::assertSame('1500000.00', $stage['available']);
        self::assertSame('62493.75', $stage['beyondBudget']);
        self::assertSame(10434, $stage['funded']);
        self::assertSame('Estructura', $stage['nextStage']);
    }

    public function testOnlyTheAdminDepositsAndTeamLeadsCannotSeeFinances(): void
    {
        $this->approve();

        $this->loginAs($this->pm);
        $this->deposit([$this->toStage($this->foundation, '1000')]);
        $this->assertStatus(403);
        $finance = $this->request('GET', $this->base.'/finance');
        $this->assertStatus(200);
        self::assertFalse($finance['permissions']['deposit']);

        $this->loginAs($this->lead);
        $this->request('GET', $this->base.'/finance');
        $this->assertStatus(403);
        $this->request('GET', $this->base.'/movements');
        $this->assertStatus(403);
    }

    public function testDepositValidation(): void
    {
        $this->approve();
        $other = $this->createProject('Otro');
        $foreignStage = $this->request('POST', '/api/projects/'.$other->getId().'/stages', ['name' => 'Ajena'])['stages'][0]['id'];

        $data = $this->deposit([]);
        $this->assertStatus(422);
        self::assertArrayHasKey('allocations', $data['violations']);

        $data = $this->deposit([$this->toStage($this->foundation, '10'), $this->toStage($foreignStage, '10')]);
        $this->assertStatus(422);
        self::assertArrayHasKey('allocations[1].stageId', $data['violations']);

        $data = $this->deposit([$this->toStage($this->foundation, '-5')]);
        $this->assertStatus(422);
        self::assertArrayHasKey('allocations[0].amount', $data['violations']);
    }

    public function testContingencyDrawMovesMoneyToAStage(): void
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

        $finance = $this->request('GET', $this->base.'/finance');
        self::assertSame('50000.00', $finance['contingency']['balance']);
        self::assertSame('150000.00', $finance['contingency']['drawn']);
        self::assertSame('150000.00', $finance['stages'][1]['contingencyDraws']);
        self::assertSame('150000.00', $finance['stages'][1]['available']);

        $this->loginAs($this->pm);
        $this->draw($this->structure, '1');
        $this->assertStatus(403);
    }

    public function testVoidingADepositRemovesItFromBalancesButKeepsTheRecord(): void
    {
        $this->approve();
        $deposit = $this->deposit([$this->toStage($this->foundation, '1000000'), ['destination' => 'CONTINGENCY', 'amount' => '200000']]);
        $this->draw($this->structure, '150000');

        // Its contingency money was already used.
        $data = $this->request('POST', '/api/movements/'.$deposit['id'].'/void', ['reason' => 'Error de digitación']);
        $this->assertStatus(422);
        self::assertSame('void_would_overdraw', $data['error']);

        $second = $this->deposit([$this->toStage($this->foundation, '500000')]);
        $voided = $this->request('POST', '/api/movements/'.$second['id'].'/void', ['reason' => 'Depósito duplicado']);
        $this->assertStatus(200);
        self::assertSame('Depósito duplicado', $voided['voided']['reason']);

        $data = $this->request('POST', '/api/movements/'.$second['id'].'/void', ['reason' => 'Otra vez']);
        self::assertSame('movement_already_voided', $data['error']);

        self::assertSame('1000000.00', $this->request('GET', $this->base.'/finance')['stages'][0]['available']);
        self::assertCount(3, $this->request('GET', $this->base.'/movements'));
    }

    public function testCompletingAStageCarriesItsLeftoverToTheNextStage(): void
    {
        $this->approve();
        $this->deposit([$this->toStage($this->foundation, '1500000')]);
        $uri = "/api/stages/{$this->foundation}/complete";

        $data = $this->request('POST', $uri, ['actualEnd' => date('Y-m-d')]);
        $this->assertStatus(422);
        self::assertSame('stage_not_in_progress', $data['error']);

        $this->loginAs($this->pm);
        $this->request('POST', "/api/stages/{$this->foundation}/start", ['actualStart' => date('Y-m-d')]);
        $this->loginAs($this->admin);
        $data = $this->request('POST', $uri, ['actualEnd' => date('Y-m-d')]);
        self::assertSame('stage_milestones_pending', $data['error']);

        $this->completeMilestones(0);
        $this->loginAs($this->pm);
        $this->request('POST', $uri, ['actualEnd' => date('Y-m-d')]);
        $this->assertStatus(403);

        $this->loginAs($this->admin);
        $finance = $this->request('POST', $uri, ['actualEnd' => date('Y-m-d')]);
        $this->assertStatus(200);
        [$foundation, $structure] = $finance['stages'];
        self::assertSame('COMPLETED', $foundation['status']);
        self::assertSame('0.00', $foundation['available']);
        self::assertSame('1500000.00', $foundation['carriedOut']);
        self::assertSame('1500000.00', $structure['carriedIn']);
        self::assertSame('1500000.00', $structure['available']);

        $movements = $this->request('GET', $this->base.'/movements');
        self::assertSame('CARRYOVER', $movements[0]['type']);
        $data = $this->request('POST', '/api/movements/'.$movements[0]['id'].'/void', ['reason' => 'x']);
        self::assertSame('movement_not_voidable', $data['error']);

        // A completed stage no longer receives money.
        $data = $this->deposit([$this->toStage($this->foundation, '10')]);
        self::assertArrayHasKey('allocations[0].stageId', $data['violations']);
    }

    public function testLeftoverOfTheLastStageGoesToContingency(): void
    {
        $this->approve();
        $this->deposit([$this->toStage($this->structure, '100000')]);
        $this->loginAs($this->pm);
        $this->request('POST', "/api/stages/{$this->structure}/start", ['actualStart' => date('Y-m-d')]);
        $this->completeMilestones(1);

        $this->loginAs($this->admin);
        $finance = $this->request('POST', "/api/stages/{$this->structure}/complete", ['actualEnd' => date('Y-m-d')]);

        self::assertSame('100000.00', $finance['contingency']['carriedIn']);
        self::assertSame('100000.00', $finance['contingency']['balance']);
    }

    public function testProofOfDepositUploadAndDownload(): void
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

        $this->loginAs($this->pm);
        $this->client->request('GET', '/api/attachments/'.$movement['attachments'][0]['id']);
        $this->assertStatus(200);
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));

        $this->loginAs($this->lead);
        $this->client->request('GET', '/api/attachments/'.$movement['attachments'][0]['id']);
        $this->assertStatus(403);
    }

    /**
     * @param list<array<string, mixed>> $allocations
     *
     * @return array<string, mixed>|null
     */
    private function deposit(array $allocations, ?string $reference = null): ?array
    {
        return $this->request('POST', $this->base.'/deposits', [
            'date' => date('Y-m-d'),
            'method' => 'TRANSFER',
            'reference' => $reference,
            'allocations' => $allocations,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toStage(int $stageId, string $amount, ?int $categoryId = null): array
    {
        return ['destination' => 'STAGE', 'stageId' => $stageId, 'categoryId' => $categoryId, 'amount' => $amount];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function draw(int $stageId, string $amount): ?array
    {
        return $this->request('POST', $this->base.'/contingency/draws', ['stageId' => $stageId, 'amount' => $amount, 'date' => date('Y-m-d'), 'reason' => 'Sobrecosto de acero']);
    }

    private function completeMilestones(int $stageIndex): void
    {
        $this->loginAs($this->pm);
        foreach ($this->plan['stages'][$stageIndex]['milestones'] as $milestone) {
            $this->request('POST', '/api/milestones/'.$milestone['id'].'/complete', ['completedAt' => date('Y-m-d')]);
            $this->assertStatus(200);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function upload(string $uri, string $content, string $name): ?array
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $content);
        $this->client->request('POST', $uri, files: ['file' => new UploadedFile($path, $name, null, null, true)], server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }
}
