<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Functional\ApiTestCase;

final class UserApiTest extends ApiTestCase
{
    public function testOnlyAdminsManageUsers(): void
    {
        $pm = $this->createUser('pm@example.com');
        $this->loginAs($pm);

        $this->request('GET', '/api/users');
        $this->assertStatus(403);
        $this->request('POST', '/api/users', ['email' => 'x@example.com', 'fullName' => 'X', 'password' => 'password123']);
        $this->assertStatus(403);
        $this->request('PATCH', '/api/users/'.$pm->getId(), ['admin' => true]);
        $this->assertStatus(403);
    }

    public function testAnAdminCreatesAUserWhoCanThenLogIn(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->json('POST', '/api/users', ['email' => 'Lead@Example.com', 'fullName' => 'Carlos Pérez', 'password' => 'another-password']);

        $this->assertStatus(201);
        self::assertSame('lead@example.com', $data['email']);
        self::assertSame('Carlos Pérez', $data['fullName']);
        self::assertTrue($data['active']);
        self::assertArrayNotHasKey('password', $data);

        $this->client->restart();
        $this->request('POST', '/api/login', ['email' => 'lead@example.com', 'password' => 'another-password']);
        $this->assertStatus(200);
    }

    public function testTheListIsAPageSortedByNameWithProjectRoles(): void
    {
        $this->loginAs($this->createUser('zoe@example.com', admin: true));
        $this->createUser('ana@example.com');

        $data = $this->json('GET', '/api/users');

        $this->assertStatus(200);
        self::assertSame(['Ana', 'Zoe'], array_column($data['items'], 'fullName'));
        self::assertSame([], $data['items'][0]['memberships']);
        self::assertSame(['total' => 2, 'page' => 1, 'perPage' => 50], ['total' => $data['total'], 'page' => $data['page'], 'perPage' => $data['perPage']]);
    }

    public function testTheListSearchesNameAndEmailAndHidesInactiveUsersUnlessAsked(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $this->createUser('ana_perez@example.com');
        $this->createUser('anaXperez@example.com');
        $this->createUser('old@example.com', active: false);

        self::assertSame(['ana_perez@example.com'], array_column($this->json('GET', '/api/users?q=ana_p')['items'], 'email'), '_ is matched literally');
        self::assertNotContains('old@example.com', array_column($this->json('GET', '/api/users')['items'], 'email'));
        self::assertContains('old@example.com', array_column($this->json('GET', '/api/users?status=all')['items'], 'email'));
        self::assertSame(['old@example.com'], array_column($this->json('GET', '/api/users?status=inactive')['items'], 'email'));
    }

    public function testCreatingValidatesEveryField(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->json('POST', '/api/users', ['email' => 'not-an-email', 'fullName' => '', 'password' => 'short']);

        $this->assertStatus(422);
        self::assertSame('validation_failed', $data['error']);
        self::assertEqualsCanonicalizing(['email', 'fullName', 'password'], array_keys($data['violations']));
    }

    public function testAnEmailBelongsToOneUserWhateverItsCase(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $pm = $this->createUser('pm@example.com');
        $lead = $this->createUser('lead@example.com');

        $this->request('POST', '/api/users', ['email' => 'PM@example.com', 'fullName' => 'Otro', 'password' => 'long-password']);
        $this->assertError(422, 'email_taken');

        $this->request('PATCH', '/api/users/'.$lead->getId(), ['email' => 'pm@EXAMPLE.com']);
        $this->assertError(422, 'email_taken');

        $this->json('PATCH', '/api/users/'.$pm->getId(), ['email' => 'PM@example.com', 'fullName' => 'Pedro']);
        $this->assertStatus(200);
    }

    public function testAnAdminEditsAUserAndResetsTheirPassword(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $pm = $this->createUser('pm@example.com');

        $data = $this->json('PATCH', '/api/users/'.$pm->getId(), ['fullName' => 'Pedro Gómez', 'password' => 'reset-password']);

        $this->assertStatus(200);
        self::assertSame('Pedro Gómez', $data['fullName']);
        $this->client->restart();
        $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => 'reset-password']);
        $this->assertStatus(200);
    }

    public function testAnAdminDisablesAUserButNotThemselves(): void
    {
        $admin = $this->createUser('admin@example.com', admin: true);
        $pm = $this->createUser('pm@example.com');
        $this->loginAs($admin);

        $data = $this->json('PATCH', '/api/users/'.$pm->getId(), ['active' => false]);
        $this->assertStatus(200);
        self::assertFalse($data['active']);

        $this->request('PATCH', '/api/users/'.$admin->getId(), ['active' => false]);
        $this->assertError(422, 'cannot_change_own_access');
        $this->request('PATCH', '/api/users/'.$admin->getId(), ['admin' => false]);
        $this->assertError(422, 'cannot_change_own_access');
    }

    public function testOnlyASuperAdminGrantsSuperAdmin(): void
    {
        $admin = $this->createUser('admin@example.com', admin: true);
        $pm = $this->createUser('pm@example.com');
        $this->loginAs($admin);

        $this->request('PATCH', '/api/users/'.$pm->getId(), ['admin' => true, 'superAdmin' => true]);
        $this->assertError(403, 'super_admin_required');
        $this->request('PATCH', '/api/users/'.$admin->getId(), ['superAdmin' => true]);
        $this->assertError(403, 'super_admin_required');
        $this->request('POST', '/api/users', ['email' => 'new@example.com', 'fullName' => 'New', 'password' => 'password123', 'superAdmin' => true]);
        $this->assertError(403, 'super_admin_required');

        $this->loginAs($this->createUser('root@example.com', superAdmin: true));
        $data = $this->json('PATCH', '/api/users/'.$pm->getId(), ['superAdmin' => true]);
        $this->assertStatus(200);
        self::assertTrue($data['superAdmin']);
        self::assertTrue($data['admin'], 'super admin implies admin');
    }

    public function testAnUnknownUserIs404(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $this->request('PATCH', '/api/users/999999', ['fullName' => 'X']);

        $this->assertError(404, 'user_not_found');
    }

    public function testAnOrdinaryAdminCannotTakeOverASuperAdminAccount(): void
    {
        $root = $this->createUser('root@example.com', superAdmin: true);
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        foreach ([['password' => 'taken-over-123'], ['email' => 'mine@example.com'], ['fullName' => 'X'], ['active' => false]] as $change) {
            $this->request('PATCH', '/api/users/'.$root->getId(), $change);
            $this->assertError(403, 'super_admin_required');
        }

        $this->client->restart();
        $this->request('POST', '/api/login', ['email' => 'root@example.com', 'password' => self::PASSWORD]);
        $this->assertStatus(200);
    }

    public function testDisablingAUserEndsTheirOpenSession(): void
    {
        $pm = $this->createUser('pm@example.com');
        $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => self::PASSWORD]);
        $this->assertStatus(200);

        $admin = $this->createUser('admin@example.com', admin: true);
        $pm->changeAccess($admin, active: false);
        $this->em->flush();

        $this->request('GET', '/api/me');
        $this->assertStatus(401);
    }

    public function testAnAdminResettingTheirOwnPasswordStaysSignedIn(): void
    {
        $admin = $this->createUser('admin@example.com', admin: true);
        $this->request('POST', '/api/login', ['email' => 'admin@example.com', 'password' => self::PASSWORD]);

        $this->request('PATCH', '/api/users/'.$admin->getId(), ['password' => 'another-password']);
        $this->assertStatus(200);

        $this->request('GET', '/api/users');
        $this->assertStatus(200);
    }
}
