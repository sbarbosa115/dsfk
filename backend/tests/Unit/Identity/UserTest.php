<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity;

use App\Identity\Domain\Model\User;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotAllowed;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailsAreStoredTrimmedAndLowercaseSoTheyStayUnique(): void
    {
        $user = $this->user('  Ana.Perez@Example.COM ');

        self::assertSame('ana.perez@example.com', $user->getEmail());
    }

    public function testANewUserIsAnActiveOrdinaryUser(): void
    {
        $user = $this->user();

        self::assertTrue($user->isActive());
        self::assertFalse($user->isAdmin());
        self::assertFalse($user->isSuperAdmin());
    }

    public function testSuperAdminIsALevelOfAdminSoGrantingItGrantsAdmin(): void
    {
        $root = $this->superAdmin();
        $user = $this->user();

        $user->changeAccess($root, superAdmin: true);

        self::assertTrue($user->isAdmin());
        self::assertTrue($user->isSuperAdmin());
    }

    public function testDroppingAdminAlsoDropsSuperAdmin(): void
    {
        $root = $this->superAdmin();
        $other = $this->superAdmin('other@example.com');

        $other->changeAccess($root, admin: false);

        self::assertFalse($other->isAdmin());
        self::assertFalse($other->isSuperAdmin(), 'nobody keeps "Ver como" without being an admin');
    }

    public function testOnlyASuperAdminGrantsOrRevokesSuperAdmin(): void
    {
        $admin = $this->user('admin@example.com');
        $admin->changeAccess($this->superAdmin(), admin: true);

        $this->expectExceptionObject(new NotAllowed('super_admin_required'));

        $this->user()->changeAccess($admin, superAdmin: true);
    }

    public function testAnOrdinaryAdminCannotPromoteItself(): void
    {
        $admin = $this->user('admin@example.com');
        $admin->changeAccess($this->superAdmin(), admin: true);

        $this->expectExceptionObject(new NotAllowed('super_admin_required'));

        $admin->changeAccess($admin, superAdmin: true);
    }

    public function testAnAdminCannotDisableThemselvesOrDropTheirOwnAccess(): void
    {
        $root = $this->superAdmin();

        foreach ([['active' => false], ['admin' => false], ['superAdmin' => false]] as $change) {
            try {
                $root->changeAccess($root, ...$change);
                self::fail('changing '.key($change).' on yourself must be refused');
            } catch (InvalidValue $e) {
                self::assertSame('cannot_change_own_access', $e->errorCode);
            }
        }
        self::assertTrue($root->isActive() && $root->isSuperAdmin());
    }

    public function testKeepingTheSameValueIsNotAChange(): void
    {
        $root = $this->superAdmin();

        $root->changeAccess($root, admin: true, superAdmin: true, active: true);

        self::assertTrue($root->isSuperAdmin());
    }

    public function testOnlyASuperAdminEditsAnotherSuperAdmin(): void
    {
        $root = $this->superAdmin();
        $admin = $this->user('admin@example.com');
        $admin->changeAccess($root, admin: true);

        $root->assertEditableBy($root);
        $root->assertEditableBy($this->superAdmin('other@example.com'));
        $this->user()->assertEditableBy($admin);

        $this->expectExceptionObject(new NotAllowed('super_admin_required'));

        $root->assertEditableBy($admin);
    }

    private function user(string $email = 'pm@example.com'): User
    {
        return new User($email, 'Ana Pérez', 'hash', new \DateTimeImmutable('2026-09-01'));
    }

    private function superAdmin(string $email = 'root@example.com'): User
    {
        return User::firstSuperAdmin($email, 'Root', 'hash', new \DateTimeImmutable('2026-09-01'));
    }
}
