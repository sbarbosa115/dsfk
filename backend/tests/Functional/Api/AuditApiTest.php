<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Identity\Domain\Model\User;
use App\Project\Domain\Model\ProjectRole;
use App\Tests\Functional\ApiTestCase;

/** The audit trail: who changed what, recorded with the change, readable by Admins only. */
final class AuditApiTest extends ApiTestCase
{
    private User $admin;
    private User $pm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin@example.com', superAdmin: true);
        $this->pm = $this->createUser('pm@example.com');
    }

    public function testChangesAreRecordedWithWhoMadeThemAndThePasswordNever(): void
    {
        $this->loginAs($this->admin);
        $project = $this->json('POST', '/api/projects', ['name' => 'Torre']);
        $this->json('PATCH', '/api/projects/'.$project['id'], ['name' => 'Torre Norte']);
        $this->json('PATCH', '/api/users/'.$this->pm->getId(), ['password' => 'another-password-1']);

        $page = $this->json('GET', '/api/audit');
        $this->assertStatus(200);
        $rename = $this->find($page['items'], 'Project', 'update');
        self::assertSame(['name' => ['Torre', 'Torre Norte']], $rename['changes']);
        self::assertSame('Admin', $rename['user']);
        self::assertSame('Torre Norte', $rename['projectName']);
        self::assertSame($project['id'], $rename['projectId']);
        self::assertSame(['***', '***'], $this->find($page['items'], 'User', 'update')['changes']['password']);
        self::assertContains('Project', $page['entityTypes']);

        $filtered = $this->json('GET', '/api/audit?entityType=User');
        self::assertSame(['User'], array_values(array_unique(array_column($filtered['items'], 'entityType'))));
    }

    public function testWhatIsDoneWhileViewingAsSomeoneElseNamesBoth(): void
    {
        $this->loginAs($this->admin);
        $project = $this->json('POST', '/api/projects', ['name' => 'Torre']);
        $this->json('POST', '/api/projects/'.$project['id'].'/members', ['userId' => $this->pm->getId(), 'role' => ProjectRole::ProjectManager->value]);
        $this->request('POST', '/api/impersonate?_switch_user=pm%40example.com');
        $this->assertStatus(302);

        $this->json('POST', '/api/projects/'.$project['id'].'/categories', ['name' => 'Materiales']);
        $this->assertStatus(200);
        $this->request('POST', '/api/impersonate?_switch_user=_exit');

        $category = $this->find($this->json('GET', '/api/audit?projectId='.$project['id'])['items'], 'Category', 'create');
        self::assertSame('Pm (vía Admin)', $category['user']);
        self::assertSame('Materiales', $category['changes']['name'][1]);
    }

    public function testOnlyAdminsReadTheTrail(): void
    {
        $this->loginAs($this->pm);
        $this->request('GET', '/api/audit');
        $this->assertStatus(403);
    }

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function find(array $items, string $type, string $action): array
    {
        foreach ($items as $item) {
            if ($type === $item['entityType'] && $action === $item['action']) {
                return $item;
            }
        }
        self::fail("No $action of $type in the trail: ".json_encode($items));
    }
}
