<?php

namespace App\Tests\Api;

class UserApiTest extends ApiTestCase
{
    public function testNonAdminCannotManageUsers(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $this->request('GET', '/api/users');

        $this->assertStatus(403);
    }

    public function testAdminCreatesUserWhoCanThenLogIn(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->request('POST', '/api/users', [
            'email' => 'lead@example.com',
            'fullName' => 'Carlos Pérez',
            'password' => 'another-password',
        ]);

        $this->assertStatus(201);
        self::assertSame('Carlos Pérez', $data['fullName']);
        self::assertArrayNotHasKey('password', $data);

        $this->client->restart();
        $this->request('POST', '/api/login', ['email' => 'lead@example.com', 'password' => 'another-password']);
        $this->assertStatus(200);
    }

    public function testCreateValidatesInput(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->request('POST', '/api/users', ['email' => 'not-an-email', 'fullName' => '', 'password' => 'short']);

        $this->assertStatus(422);
        self::assertSame('validation_failed', $data['error']);
        self::assertArrayHasKey('email', $data['violations']);
        self::assertArrayHasKey('fullName', $data['violations']);
        self::assertArrayHasKey('password', $data['violations']);
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));
        $this->createUser('pm@example.com');

        $data = $this->request('POST', '/api/users', ['email' => 'PM@example.com', 'fullName' => 'Otro', 'password' => 'long-password']);

        $this->assertStatus(422);
        self::assertSame('email_taken', $data['error']);
    }

    public function testAdminCanDisableUserButNotThemselves(): void
    {
        $admin = $this->createUser('admin@example.com', admin: true);
        $pm = $this->createUser('pm@example.com');
        $this->loginAs($admin);

        $data = $this->request('PATCH', '/api/users/'.$pm->getId(), ['active' => false]);
        $this->assertStatus(200);
        self::assertFalse($data['active']);

        $this->request('PATCH', '/api/users/'.$admin->getId(), ['active' => false]);
        $this->assertStatus(422);
        $this->request('PATCH', '/api/users/'.$admin->getId(), ['admin' => false]);
        $this->assertStatus(422);
    }

    public function testOnlyASuperAdminGrantsSuperAdmin(): void
    {
        $admin = $this->createUser('admin@example.com', admin: true);
        $pm = $this->createUser('pm@example.com');
        $this->loginAs($admin);

        // An ordinary admin can neither promote someone else nor promote itself.
        $this->request('PATCH', '/api/users/'.$pm->getId(), ['admin' => true, 'superAdmin' => true]);
        $this->assertStatus(403);
        $this->request('PATCH', '/api/users/'.$admin->getId(), ['superAdmin' => true]);
        $this->assertStatus(403);
        $this->request('POST', '/api/users', ['email' => 'new@example.com', 'fullName' => 'New', 'password' => 'password123', 'superAdmin' => true]);
        $this->assertStatus(403);

        $this->loginAs($this->createUser('root@example.com', admin: true, superAdmin: true));
        $data = $this->request('PATCH', '/api/users/'.$pm->getId(), ['superAdmin' => true]);
        $this->assertStatus(200);
        self::assertTrue($data['superAdmin']);
        self::assertTrue($data['admin'], 'super admin implies admin');
    }

    public function testDroppingAdminAlsoDropsSuperAdmin(): void
    {
        $root = $this->createUser('root@example.com', admin: true, superAdmin: true);
        $other = $this->createUser('other@example.com', admin: true, superAdmin: true);
        $this->loginAs($root);

        $data = $this->request('PATCH', '/api/users/'.$other->getId(), ['admin' => false]);
        $this->assertStatus(200);
        self::assertFalse($data['admin']);
        self::assertFalse($data['superAdmin'], 'you cannot keep "Ver como" without being an admin');
    }
}
