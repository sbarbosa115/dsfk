<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Project\Domain\Model\ProjectRole;
use App\Tests\Functional\ApiTestCase;

final class ProjectApiTest extends ApiTestCase
{
    public function testAnAdminCreatesAProjectInTheDefaultCurrency(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->json('POST', '/api/projects', ['name' => 'Edificio Las Palmas', 'plannedStart' => '2026-10-01', 'plannedEnd' => '2027-12-31']);

        $this->assertStatus(201);
        self::assertSame('COP', $data['currency']);
        self::assertSame('DRAFT', $data['status']);
        self::assertSame('2026-10-01', $data['plannedStart']);
        self::assertSame([], $data['members']);
        self::assertSame('ADMIN', $data['myRole']);
    }

    public function testNewProjectsUseTheCurrencyInSettings(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $this->request('PUT', '/api/settings', ['defaultCurrency' => 'USD']);

        self::assertSame('USD', $this->json('POST', '/api/projects', ['name' => 'Nuevo'])['currency']);
        self::assertSame('EUR', $this->json('POST', '/api/projects', ['name' => 'Otro', 'currency' => 'EUR'])['currency']);
    }

    public function testCreatingValidatesTheFields(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->json('POST', '/api/projects', ['name' => '', 'currency' => 'XXZ', 'plannedStart' => 'mañana']);
        $this->assertStatus(422);
        self::assertEqualsCanonicalizing(['name', 'currency', 'plannedStart'], array_keys($data['violations']));

        $data = $this->json('POST', '/api/projects', ['name' => 'X', 'plannedStart' => '2027-01-01', 'plannedEnd' => '2026-01-01']);
        $this->assertStatus(422);
        self::assertArrayHasKey('plannedEnd', $data['violations']);
    }

    public function testOnlyAdminsCreateProjects(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $this->request('POST', '/api/projects', ['name' => 'X']);

        $this->assertStatus(403);
    }

    public function testMembersSeeOnlyTheirProjectsAndOthersAreNotFound(): void
    {
        $lead = $this->createUser('lead@example.com');
        $mine = $this->createProject('Mío', [[$lead, ProjectRole::TeamLead]]);
        $other = $this->createProject('Ajeno');
        $this->loginAs($lead);

        self::assertSame(['Mío'], array_column($this->json('GET', '/api/projects')['items'], 'name'));
        self::assertSame('TEAM_LEAD', $this->json('GET', '/api/projects/'.$mine->getId())['myRole']);

        $this->request('GET', '/api/projects/'.$other->getId());
        $this->assertError(404, 'project_not_found');
        $this->request('GET', '/api/projects/999999');
        $this->assertError(404, 'project_not_found');
    }

    public function testAdminsSeeEveryProjectNewestFirstAndCanSearchByName(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $this->createProject('Torre Norte');
        $this->createProject('Casa 50%');
        $this->createProject('Torre Sur');

        $page = $this->json('GET', '/api/projects');
        self::assertSame(['Torre Sur', 'Casa 50%', 'Torre Norte'], array_column($page['items'], 'name'));
        self::assertSame(3, $page['total']);
        self::assertSame(['Torre Sur', 'Torre Norte'], array_column($this->json('GET', '/api/projects?q=torre')['items'], 'name'));
        self::assertSame(['Casa 50%'], array_column($this->json('GET', '/api/projects?q=50%25')['items'], 'name'), '% is matched literally');
        self::assertSame(['Torre Norte'], array_column($this->json('GET', '/api/projects?page=2&perPage=2')['items'], 'name'));
        self::assertSame(['Torre Norte'], array_column($this->json('GET', '/api/projects?status=DRAFT&q=norte')['items'], 'name'));
        self::assertSame([], $this->json('GET', '/api/projects?status=ACTIVE')['items']);
    }

    public function testMembersCannotEditProjects(): void
    {
        $pm = $this->createUser('pm@example.com');
        $project = $this->createProject('Mío', [[$pm, ProjectRole::ProjectManager]]);
        $this->loginAs($pm);

        $this->request('PATCH', '/api/projects/'.$project->getId(), ['name' => 'Otro']);
        $this->assertError(403, 'forbidden');

        $this->request('POST', '/api/projects/'.$project->getId().'/members', ['userId' => $pm->getId(), 'role' => 'TEAM_LEAD']);
        $this->assertStatus(403);
    }

    public function testAnAdminEditsTheProjectButNotItsCurrency(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $project = $this->createProject();

        $data = $this->json('PATCH', '/api/projects/'.$project->getId(), ['name' => 'Torre', 'description' => 'Doce pisos', 'status' => 'ARCHIVED', 'plannedStart' => '2026-01-01', 'currency' => 'COP']);
        $this->assertStatus(200);
        self::assertSame(['Torre', 'Doce pisos', 'ARCHIVED', '2026-01-01'], [$data['name'], $data['description'], $data['status'], $data['plannedStart']]);

        $this->request('PATCH', '/api/projects/'.$project->getId(), ['currency' => 'USD']);
        $this->assertError(422, 'currency_locked');

        $data = $this->json('PATCH', '/api/projects/'.$project->getId(), ['description' => '', 'plannedStart' => null]);
        self::assertNull($data['description'], 'an empty description clears it');
        self::assertNull($data['plannedStart'], 'null clears a date');
    }

    public function testAProjectHasOneProjectManager(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $pm = $this->createUser('pm@example.com');
        $pm2 = $this->createUser('pm2@example.com');
        $uri = '/api/projects/'.$this->createProject()->getId().'/members';

        $data = $this->json('POST', $uri, ['userId' => $pm->getId(), 'role' => 'PROJECT_MANAGER']);
        $this->assertStatus(200);
        self::assertSame('pm@example.com', $data['members'][0]['user']['email']);

        $this->request('POST', $uri, ['userId' => $pm2->getId(), 'role' => 'PROJECT_MANAGER']);
        $this->assertError(409, 'project_manager_exists');

        $this->request('POST', $uri, ['userId' => $pm->getId(), 'role' => 'TEAM_LEAD']);
        $this->assertStatus(200);
        $this->request('POST', $uri, ['userId' => $pm2->getId(), 'role' => 'PROJECT_MANAGER']);
        $this->assertStatus(200);
    }

    public function testOnlyActiveNonAdminUsersBecomeMembers(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $uri = '/api/projects/'.$this->createProject()->getId().'/members';

        $this->request('POST', $uri, ['userId' => $this->createUser('boss@example.com', admin: true)->getId(), 'role' => 'TEAM_LEAD']);
        $this->assertError(422, 'admin_is_global');
        $this->request('POST', $uri, ['userId' => $this->createUser('old@example.com', active: false)->getId(), 'role' => 'TEAM_LEAD']);
        $this->assertError(422, 'user_inactive');
        $this->request('POST', $uri, ['userId' => 999999, 'role' => 'TEAM_LEAD']);
        $this->assertError(422, 'user_not_found');
        $data = $this->json('POST', $uri, ['userId' => 1, 'role' => 'OWNER']);
        $this->assertStatus(422);
        self::assertArrayHasKey('role', $data['violations']);
    }

    public function testRemovingAMember(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $lead = $this->createUser('lead@example.com');
        $project = $this->createProject('P', [[$lead, ProjectRole::TeamLead]]);
        $memberId = $this->json('GET', '/api/projects/'.$project->getId())['members'][0]['id'];

        $this->request('DELETE', '/api/projects/'.$project->getId().'/members/'.$memberId);
        $this->assertStatus(204);
        self::assertSame([], $this->json('GET', '/api/projects/'.$project->getId())['members']);

        $this->request('DELETE', '/api/projects/'.$project->getId().'/members/'.$memberId);
        $this->assertError(404, 'member_not_found');
    }

    public function testTheSignedInUserAndTheUsersListShowProjectRoles(): void
    {
        $pm = $this->createUser('pm@example.com');
        $project = $this->createProject('Torre Norte', [[$pm, ProjectRole::ProjectManager]]);

        $this->loginAs($pm);
        self::assertSame([['projectId' => $project->getId(), 'projectName' => 'Torre Norte', 'role' => 'PROJECT_MANAGER']], $this->json('GET', '/api/me')['memberships']);

        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $users = $this->json('GET', '/api/users?q=pm@')['items'];
        self::assertIsArray($users);
        $row = array_values(array_filter($users, static fn (array $u): bool => 'pm@example.com' === $u['email']))[0];
        self::assertSame('Torre Norte', $row['memberships'][0]['projectName']);
    }

    public function testAMemberOfAnotherProjectCannotBeRemovedThroughThisOne(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $lead = $this->createUser('lead@example.com');
        $other = $this->createProject('Otro', [[$lead, ProjectRole::TeamLead]]);
        $mine = $this->createProject('Mío');
        $memberId = $this->json('GET', '/api/projects/'.$other->getId())['members'][0]['id'];

        $this->request('DELETE', '/api/projects/'.$mine->getId().'/members/'.$memberId);

        $this->assertError(404, 'member_not_found');
        self::assertCount(1, $this->json('GET', '/api/projects/'.$other->getId())['members']);
    }

    public function testAnUnknownStatusFilterIsAClientError(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $this->request('GET', '/api/projects?status=DELETED');

        self::assertGreaterThanOrEqual(400, $this->client->getResponse()->getStatusCode());
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }
}
