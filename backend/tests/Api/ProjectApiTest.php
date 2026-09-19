<?php

namespace App\Tests\Api;

use App\Enum\ProjectRole;

class ProjectApiTest extends ApiTestCase
{
    public function testAdminCreatesProjectWithDefaultCurrency(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->request('POST', '/api/projects', [
            'name' => 'Edificio Las Palmas',
            'plannedStart' => '2026-10-01',
            'plannedEnd' => '2027-12-31',
        ]);

        $this->assertStatus(201);
        self::assertSame('COP', $data['currency']);
        self::assertSame('DRAFT', $data['status']);
        self::assertSame([], $data['members']);
    }

    public function testPlannedEndCannotBeBeforeStart(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->request('POST', '/api/projects', ['name' => 'X', 'plannedStart' => '2027-01-01', 'plannedEnd' => '2026-01-01']);

        $this->assertStatus(422);
        self::assertArrayHasKey('plannedEnd', $data['violations']);
    }

    public function testProjectManagerCannotCreateProjects(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $this->request('POST', '/api/projects', ['name' => 'X']);

        $this->assertStatus(403);
    }

    public function testMembersOnlySeeTheirProjects(): void
    {
        $lead = $this->createUser('lead@example.com');
        $mine = $this->createProject('Mío', [[$lead, ProjectRole::TeamLead]]);
        $other = $this->createProject('Ajeno');
        $this->loginAs($lead);

        $list = $this->request('GET', '/api/projects');
        self::assertSame(['Mío'], array_column($list, 'name'));

        $this->request('GET', '/api/projects/'.$mine->getId());
        $this->assertStatus(200);
        $this->request('GET', '/api/projects/'.$other->getId());
        $this->assertStatus(403);
    }

    public function testMembersCannotEditProjects(): void
    {
        $pm = $this->createUser('pm@example.com');
        $project = $this->createProject('Mío', [[$pm, ProjectRole::ProjectManager]]);
        $this->loginAs($pm);

        $this->request('PATCH', '/api/projects/'.$project->getId(), ['name' => 'Otro']);

        $this->assertStatus(403);
    }

    public function testProjectAllowsOnlyOneProjectManager(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $pm = $this->createUser('pm@example.com');
        $pm2 = $this->createUser('pm2@example.com');
        $project = $this->createProject();
        $uri = '/api/projects/'.$project->getId().'/members';

        $data = $this->request('POST', $uri, ['userId' => $pm->getId(), 'role' => 'PROJECT_MANAGER']);
        $this->assertStatus(200);
        self::assertSame('pm@example.com', $data['members'][0]['user']['email']);

        $data = $this->request('POST', $uri, ['userId' => $pm2->getId(), 'role' => 'PROJECT_MANAGER']);
        $this->assertStatus(422);
        self::assertSame('project_manager_exists', $data['error']);

        // Changing the existing PM's own role is fine.
        $this->request('POST', $uri, ['userId' => $pm->getId(), 'role' => 'TEAM_LEAD']);
        $this->assertStatus(200);
        $this->request('POST', $uri, ['userId' => $pm2->getId(), 'role' => 'PROJECT_MANAGER']);
        $this->assertStatus(200);
    }

    public function testRemoveMember(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $lead = $this->createUser('lead@example.com');
        $project = $this->createProject('P', [[$lead, ProjectRole::TeamLead]]);
        $memberId = $project->getMembers()->first()->getId();

        $this->request('DELETE', '/api/projects/'.$project->getId().'/members/'.$memberId);
        $this->assertStatus(204);

        $data = $this->request('GET', '/api/projects/'.$project->getId());
        self::assertSame([], $data['members']);
    }

    public function testCurrencyIsLockedAfterCreation(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $project = $this->createProject();

        $data = $this->request('PATCH', '/api/projects/'.$project->getId(), ['currency' => 'USD']);

        $this->assertStatus(422);
        self::assertSame('currency_locked', $data['error']);
    }
}
