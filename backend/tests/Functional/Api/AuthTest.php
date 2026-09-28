<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Functional\ApiTestCase;

final class AuthTest extends ApiTestCase
{
    public function testLoginIsCaseInsensitiveAndReturnsTheCurrentUser(): void
    {
        $this->createUser('pm@example.com');

        $data = $this->json('POST', '/api/login', ['email' => 'PM@example.com', 'password' => self::PASSWORD]);

        $this->assertStatus(200);
        self::assertSame('pm@example.com', $data['email']);
        self::assertFalse($data['admin']);
        self::assertSame([], $data['memberships']);
        self::assertNull($data['impersonator']);
        self::assertArrayNotHasKey('password', $data);

        $this->request('GET', '/api/me');
        $this->assertStatus(200);
    }

    public function testAWrongPasswordAndAnUnknownEmailGetTheSameAnswer(): void
    {
        $this->createUser('pm@example.com');

        $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => 'wrong-password']);
        $this->assertError(401, 'invalid_credentials');

        $this->request('POST', '/api/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
        $this->assertError(401, 'invalid_credentials');
    }

    public function testADisabledUserCannotLogIn(): void
    {
        $this->createUser('old@example.com', active: false);

        $this->request('POST', '/api/login', ['email' => 'old@example.com', 'password' => self::PASSWORD]);

        $this->assertError(401, 'account_disabled');
    }

    public function testAMalformedLoginBodyIsAClientError(): void
    {
        $this->request('POST', '/api/login', ['email' => 'pm@example.com']);

        self::assertGreaterThanOrEqual(400, $this->client->getResponse()->getStatusCode());
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    public function testTheApiNeedsASession(): void
    {
        $this->request('GET', '/api/me');

        $this->assertError(401, 'authentication_required');
    }

    public function testLoggingOutEndsTheSession(): void
    {
        $this->request('POST', '/api/login', ['email' => $this->createUser('pm@example.com')->getEmail(), 'password' => self::PASSWORD]);

        $this->request('POST', '/api/logout');
        $this->assertStatus(204);

        $this->request('GET', '/api/me');
        $this->assertStatus(401);
    }

    public function testAUserChangesTheirOwnPasswordAfterProvingTheCurrentOne(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $data = $this->json('POST', '/api/me/password', ['currentPassword' => 'wrong-one', 'newPassword' => 'brand-new-password']);
        $this->assertStatus(422);
        self::assertArrayHasKey('currentPassword', $data['violations']);

        $data = $this->json('POST', '/api/me/password', ['currentPassword' => self::PASSWORD, 'newPassword' => self::PASSWORD]);
        $this->assertStatus(422);
        self::assertArrayHasKey('newPassword', $data['violations'], 'the new password must differ');

        $this->request('POST', '/api/me/password', ['currentPassword' => self::PASSWORD, 'newPassword' => 'brand-new-password']);
        $this->assertStatus(204);

        $this->client->restart();
        $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => 'brand-new-password']);
        $this->assertStatus(200);
    }

    public function testFiveFailedLoginsLockTheAccountForAWhile(): void
    {
        $this->createUser('pm@example.com');

        for ($i = 0; $i < 5; ++$i) {
            $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => 'wrong-password']);
        }
        $this->request('POST', '/api/login', ['email' => 'pm@example.com', 'password' => self::PASSWORD]);

        $this->assertError(401, 'too_many_attempts');
    }
}
