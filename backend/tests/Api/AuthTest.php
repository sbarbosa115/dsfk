<?php

namespace App\Tests\Api;

use App\Enum\ProjectRole;

class AuthTest extends ApiTestCase
{
    public function testLoginReturnsCurrentUserWithMemberships(): void
    {
        $pm = $this->createUser('pm@example.com');
        $this->createProject('Torre Norte', [[$pm, ProjectRole::ProjectManager]]);

        $data = $this->request('POST', '/api/login', ['email' => 'PM@example.com', 'password' => self::PASSWORD]);

        $this->assertStatus(200);
        self::assertSame('pm@example.com', $data['email']);
        self::assertFalse($data['admin']);
        self::assertSame('Torre Norte', $data['memberships'][0]['projectName']);
        self::assertSame('PROJECT_MANAGER', $data['memberships'][0]['role']);

        $this->request('GET', '/api/me');
        $this->assertStatus(200);
    }

    public function testWrongPasswordIsRejected(): void
    {
        $this->createUser('pm@example.com');

        $data = $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => 'wrong-password']);

        $this->assertStatus(401);
        self::assertSame('invalid_credentials', $data['error']);
    }

    public function testDisabledUserCannotLogin(): void
    {
        $this->createUser('old@example.com', active: false);

        $data = $this->request('POST', '/api/login', ['email' => 'old@example.com', 'password' => self::PASSWORD]);

        $this->assertStatus(401);
        self::assertSame('account_disabled', $data['error']);
    }

    public function testApiRequiresAuthentication(): void
    {
        $this->request('GET', '/api/me');
        $this->assertStatus(401);
    }

    public function testStateChangingRequestWithoutCsrfHeaderIsRejected(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $this->request('POST', '/api/projects', ['name' => 'X'], csrfHeader: false);

        $this->assertStatus(403);
    }

    public function testLogout(): void
    {
        $this->request('POST', '/api/login', ['email' => $this->createUser('pm@example.com')->getEmail(), 'password' => self::PASSWORD]);

        $this->request('POST', '/api/logout');
        $this->assertStatus(204);

        $this->request('GET', '/api/me');
        $this->assertStatus(401);
    }

    public function testUserChangesTheirOwnPassword(): void
    {
        $user = $this->createUser('pm@example.com');
        $this->loginAs($user);

        $data = $this->request('POST', '/api/me/password', ['currentPassword' => 'wrong-one', 'newPassword' => 'brand-new-password']);
        $this->assertStatus(422);
        self::assertArrayHasKey('currentPassword', $data['violations']);

        $this->request('POST', '/api/me/password', ['currentPassword' => self::PASSWORD, 'newPassword' => 'brand-new-password']);
        $this->assertStatus(204);

        $this->client->restart();
        $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => 'brand-new-password']);
        $this->assertStatus(200);
    }
}
