<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Enum\ProjectRole;
use App\Security\ImpersonationVoter;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ImpersonationTest extends ApiTestCase
{
    private User $admin;
    private User $pm;
    private User $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createUser('admin@example.com', admin: true, superAdmin: true);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead]]);
    }

    public function testSuperAdminViewsTheAppAsAnotherUserAndComesBack(): void
    {
        $this->loginAs($this->admin);
        self::assertTrue($this->request('GET', '/api/me')['canImpersonate']);

        $me = $this->switchTo('pm@example.com');
        self::assertSame('pm@example.com', $me['email']);
        self::assertSame(['id' => $this->admin->getId(), 'fullName' => 'Admin'], $me['impersonator']);
        self::assertSame('PROJECT_MANAGER', $me['memberships'][0]['role']);
        self::assertTrue($me['canImpersonate'], 'the banner must offer a way back');

        // Acting as the PM: admin-only endpoints are closed.
        $this->request('GET', '/api/users');
        $this->assertStatus(403);

        // Straight from one user to another.
        self::assertSame('lead@example.com', $this->switchTo('lead@example.com')['email']);

        $me = $this->switchTo('_exit');
        self::assertSame('admin@example.com', $me['email']);
        self::assertNull($me['impersonator']);
    }

    public function testChangesMadeWhileImpersonatingNameTheRealPerson(): void
    {
        $this->loginAs($this->admin);
        $this->switchTo('pm@example.com');

        $this->request('POST', '/api/me/password', ['currentPassword' => self::PASSWORD, 'newPassword' => 'new-password-123']);
        $this->assertStatus(204);

        $this->switchTo('_exit');
        $log = $this->request('GET', '/api/audit?entityType=User')['items'][0];
        self::assertSame('Pm (vía Admin)', $log['user']);
    }

    public function testSwitchingNeedsAPostWithTheCsrfHeader(): void
    {
        $this->loginAs($this->admin);

        $data = $this->request('GET', '/api/impersonate?_switch_user=pm@example.com');
        $this->assertStatus(403);
        self::assertSame('switch_user_not_allowed', $data['error']);

        $this->request('POST', '/api/impersonate?_switch_user=pm@example.com', csrfHeader: false);
        $this->assertStatus(403);

        self::assertSame('admin@example.com', $this->request('GET', '/api/me')['email']);
    }

    public function testOnlySuperAdminsSwitchAndOnlyToActiveNonAdminUsers(): void
    {
        $this->createUser('admin2@example.com', admin: true);
        $this->createUser('old@example.com', active: false);

        $this->loginAs($this->pm);
        self::assertFalse($this->request('GET', '/api/me')['canImpersonate']);
        $this->request('POST', '/api/impersonate?_switch_user=lead@example.com');
        $this->assertStatus(403);

        $this->loginAs($this->admin);
        foreach (['admin2@example.com', 'old@example.com', 'admin@example.com'] as $target) {
            $this->request('POST', '/api/impersonate?_switch_user='.$target);
            $this->assertStatus(403);
        }
    }

    public function testAnOrdinaryAdminCannotSwitchOrSeeTheMenu(): void
    {
        $plainAdmin = $this->createUser('admin3@example.com', admin: true);

        $this->loginAs($plainAdmin);
        $me = $this->request('GET', '/api/me');
        self::assertFalse($me['superAdmin']);
        self::assertFalse($me['canImpersonate'], 'the "Ver como" menu is for super admins only');

        $this->request('POST', '/api/impersonate?_switch_user=pm@example.com');
        $this->assertStatus(403);
    }

    public function testDisabledOutsideDevAndTestUnlessConfigured(): void
    {
        $voter = new ImpersonationVoter(false);
        $token = new UsernamePasswordToken($this->admin, 'main', $this->admin->getRoles());

        self::assertSame(-1, $voter->vote($token, $this->pm, [ImpersonationVoter::ATTRIBUTE]));
        self::assertSame(1, (new ImpersonationVoter(true))->vote($token, $this->pm, [ImpersonationVoter::ATTRIBUTE]));
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
        $me = $this->request('GET', '/api/impersonate');
        self::assertSame($me, $this->request('GET', '/api/me'));

        return $me;
    }
}
