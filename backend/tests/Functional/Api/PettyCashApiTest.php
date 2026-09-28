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

/** Caja menor cycles: what moved in each, closing by the PM, sign-off by an Admin. */
final class PettyCashApiTest extends ApiTestCase
{
    use BuildsPlan;

    private const TODAY = '2026-10-15';

    private User $lead;
    private Project $project;
    private int $foundation;
    private int $materials;

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
        $this->foundation = $plan['stages'][0]['id'];
        $this->materials = array_column($plan['categories'], 'id', 'name')['Materiales'];
        $this->approve();
    }

    public function testAnUnusedCajaMenorShowsItsFirstCycleEmpty(): void
    {
        $this->loginAs($this->pm);
        $cash = $this->json('GET', $this->base.'/petty-cash');

        self::assertSame('0.00', $cash['balance']);
        self::assertNull($cash['current']['id']);
        self::assertSame(1, $cash['current']['number']);
        self::assertSame([], $cash['history']);
        self::assertTrue($cash['permissions']['close']);
        self::assertFalse($cash['permissions']['signOff']);

        $this->loginAs($this->lead);
        $this->request('GET', $this->base.'/petty-cash');
        $this->assertError(403, 'forbidden');
    }

    public function testACycleIsClosedByThePmSignedOffByAnAdminAndItsMovementsStayAsTheyWere(): void
    {
        $this->loginAs($this->admin);
        $this->json('POST', $this->base.'/deposits', ['date' => self::TODAY, 'method' => 'CASH', 'note' => 'Fondo inicial', 'allocations' => [['destination' => 'PETTY_CASH', 'amount' => '300000']]]);
        $this->loginAs($this->pm);
        $expense = $this->json('POST', $this->base.'/expenses', ['stageId' => $this->foundation, 'categoryId' => $this->materials, 'date' => self::TODAY, 'amount' => '45000', 'description' => 'Clavos', 'paidFrom' => 'PETTY_CASH']);
        $this->assertStatus(201);

        $cash = $this->json('GET', $this->base.'/petty-cash');
        $current = $cash['current'];
        self::assertSame('255000.00', $cash['balance']);
        self::assertSame(1, $current['number']);
        self::assertSame('0.00', $current['openingBalance']);
        self::assertSame('300000.00', $current['topUps']);
        self::assertSame('45000.00', $current['spent']);
        self::assertSame('255000.00', $current['closingBalance']);
        self::assertSame(['DEPOSIT', 'EXPENSE'], array_column($current['movements'], 'type'));
        self::assertSame(['300000.00', '-45000.00'], array_column($current['movements'], 'amount'));
        self::assertSame('Clavos', $current['movements'][1]['description']);
        self::assertSame('CASH', $current['movements'][0]['method']);

        $closed = $this->json('POST', $this->base.'/petty-cash/close', ['note' => 'Se acabó el mes']);
        $this->assertStatus(200);
        self::assertSame('CLOSED', $closed['status']);
        self::assertSame('255000.00', $closed['closingBalance']);
        self::assertSame('Pm', $closed['closedBy']);

        $cash = $this->json('GET', $this->base.'/petty-cash');
        self::assertNull($cash['current']['id']);
        self::assertSame(2, $cash['current']['number']);
        self::assertSame('255000.00', $cash['current']['openingBalance']);
        self::assertSame(1, $cash['unsignedCount']);

        // What a closed cycle holds is final.
        $this->loginAs($this->admin);
        $this->request('POST', "/api/expenses/{$expense['id']}/void", ['reason' => 'x']);
        $this->assertError(409, 'cycle_closed');

        $this->loginAs($this->pm);
        $this->request('POST', "/api/petty-cash-cycles/{$closed['id']}/sign-off");
        $this->assertError(403, 'forbidden');
        $this->loginAs($this->admin);
        $signed = $this->json('POST', "/api/petty-cash-cycles/{$closed['id']}/sign-off");
        self::assertSame('SIGNED_OFF', $signed['status']);
        self::assertSame('Admin', $signed['signedOffBy']);
        $this->request('POST', "/api/petty-cash-cycles/{$closed['id']}/sign-off");
        $this->assertError(409, 'cycle_not_closed');

        // The next use opens cycle 2 with the closing balance.
        $this->loginAs($this->pm);
        $this->json('POST', $this->base.'/expenses', ['stageId' => $this->foundation, 'categoryId' => $this->materials, 'date' => self::TODAY, 'amount' => '5000', 'description' => 'Tornillos', 'paidFrom' => 'PETTY_CASH']);
        $cash = $this->json('GET', $this->base.'/petty-cash');
        self::assertSame(2, $cash['current']['number']);
        self::assertSame('255000.00', $cash['current']['openingBalance']);
        self::assertSame('250000.00', $cash['current']['closingBalance']);
        self::assertSame(['Tornillos'], array_column($cash['current']['movements'], 'description'));
        self::assertSame([1], array_column($cash['history'], 'number'));
    }

    public function testAnotherProjectsPeopleDoNotReachItsCycles(): void
    {
        $this->loginAs($this->pm);
        $closed = $this->json('POST', $this->base.'/petty-cash/close', []);
        $otherPm = $this->createUser('otro-pm@example.com');
        $this->createProject('Otro', [[$otherPm, ProjectRole::ProjectManager]]);
        $this->loginAs($otherPm);

        $this->request('GET', "/api/petty-cash-cycles/{$closed['id']}");
        $this->assertError(404, 'project_not_found');
        $this->request('POST', $this->base.'/petty-cash/close', []);
        $this->assertError(404, 'project_not_found');
    }
}
