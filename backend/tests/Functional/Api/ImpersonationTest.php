<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Identity\Domain\Model\User;
use App\Tests\Functional\ApiTestCase;

final class ImpersonationTest extends ApiTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin@example.com', superAdmin: true);
        $this->createUser('pm@example.com');
        $this->createUser('lead@example.com');
    }

    public function testASuperAdminViewsTheAppAsAnotherUserAndComesBack(): void
    {
        $this->loginAs($this->admin);
        self::assertTrue($this->json('GET', '/api/me')['canImpersonate']);

        $me = $this->switchTo('pm@example.com');
        self::assertSame('pm@example.com', $me['email']);
        self::assertSame(['id' => $this->admin->getId(), 'fullName' => 'Admin'], $me['impersonator']);
        self::assertTrue($me['canImpersonate'], 'the banner must offer a way back');

        // Acting as the PM, admin-only endpoints are closed.
        $this->request('GET', '/api/users');
        $this->assertStatus(403);

        // Straight from one user to another.
        self::assertSame('lead@example.com', $this->switchTo('lead@example.com')['email']);

        $me = $this->switchTo('_exit');
        self::assertSame('admin@example.com', $me['email']);
        self::assertNull($me['impersonator']);
    }

    public function testSwitchingNeedsAPostWithTheCsrfHeader(): void
    {
        $this->loginAs($this->admin);

        $this->request('GET', '/api/impersonate?_switch_user=pm@example.com');
        $this->assertError(403, 'switch_user_not_allowed');

        $this->request('POST', '/api/impersonate?_switch_user=pm@example.com', csrfHeader: false);
        $this->assertStatus(403);

        self::assertSame('admin@example.com', $this->json('GET', '/api/me')['email']);
    }

    public function testOnlySuperAdminsSwitchAndOnlyToActiveNonAdminUsersOtherThanThemselves(): void
    {
        $this->createUser('admin2@example.com', admin: true);
        $this->createUser('old@example.com', active: false);

        $this->loginAs($this->createUser('plain-admin@example.com', admin: true));
        self::assertFalse($this->json('GET', '/api/me')['canImpersonate'], '"Ver como" is for super admins only');
        $this->request('POST', '/api/impersonate?_switch_user=pm@example.com');
        $this->assertStatus(403);

        $this->loginAs($this->admin);
        foreach (['admin2@example.com', 'old@example.com', 'admin@example.com'] as $target) {
            $this->request('POST', '/api/impersonate?_switch_user='.$target);
            $this->assertStatus(403);
        }
    }

    /**
     * @return array<string, mixed> /api/me after the switch
     */
    private function switchTo(string $identifier): array
    {
        $this->request('POST', '/api/impersonate?_switch_user='.urlencode($identifier));
        $this->assertStatus(302);
        self::assertStringEndsWith('/api/impersonate', (string) $this->client->getResponse()->headers->get('Location'));

        // What the browser does with the redirect.
        $me = $this->json('GET', '/api/impersonate');
        self::assertSame($me, $this->json('GET', '/api/me'));

        return $me;
    }
}
