<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotAllowed;
use App\Shared\Domain\Model\Audited;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A person who signs in. Admin is global; super admin is a level of admin that may also grant super admin and
 * "view as" other users. Project roles live in the Project context.
 */
#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', fields: ['email'])]
class User implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 150)]
    private string $fullName;

    #[ORM\Column]
    private string $password;

    #[ORM\Column]
    private bool $admin = false;

    #[ORM\Column(options: ['default' => 0])]
    private bool $superAdmin = false;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email, string $fullName, string $passwordHash, \DateTimeImmutable $createdAt)
    {
        $this->email = self::normalizeEmail($email);
        $this->fullName = trim($fullName);
        $this->password = $passwordHash;
        $this->createdAt = $createdAt;
    }

    /**
     * The first admin of an install is a super admin: only a super admin can grant that level, so without this
     * nobody could ever hand it out.
     */
    public static function firstSuperAdmin(string $email, string $fullName, string $passwordHash, \DateTimeImmutable $createdAt): self
    {
        $user = new self($email, $fullName, $passwordHash, $createdAt);
        $user->admin = true;
        $user->superAdmin = true;

        return $user;
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getPasswordHash(): string
    {
        return $this->password;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->superAdmin;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Only a super admin edits a super admin (other than themselves): otherwise an ordinary admin could reset
     * a super admin's password or email and sign in as them.
     */
    public function assertEditableBy(self $by): void
    {
        if ($this->superAdmin && !$by->superAdmin && !$this->isSameAs($by)) {
            throw new NotAllowed('super_admin_required');
        }
    }

    public function changeEmail(string $email): void
    {
        $this->email = self::normalizeEmail($email);
    }

    public function rename(string $fullName): void
    {
        $this->fullName = trim($fullName);
    }

    public function changePassword(string $passwordHash): void
    {
        $this->password = $passwordHash;
    }

    /**
     * Grants or revokes admin, super admin and the active flag. A null leaves that flag as it is.
     *
     * - Only a super admin grants or revokes super admin, so an ordinary admin cannot promote anyone, itself
     *   included.
     * - Nobody disables themselves or drops their own access (they would lock themselves out).
     * - Super admin implies admin; dropping admin drops super admin.
     */
    public function changeAccess(self $by, ?bool $admin = null, ?bool $superAdmin = null, ?bool $active = null): void
    {
        $admin = $admin ?? $this->admin;
        $superAdmin = $superAdmin ?? ($admin ? $this->superAdmin : false);
        $active = $active ?? $this->active;
        if ($superAdmin) {
            $admin = true;
        }

        if ($this->isSameAs($by) && ((!$admin && $this->admin) || (!$superAdmin && $this->superAdmin) || (!$active && $this->active))) {
            throw new InvalidValue('cannot_change_own_access');
        }
        if ($superAdmin !== $this->superAdmin && !$by->superAdmin) {
            throw new NotAllowed('super_admin_required');
        }

        $this->admin = $admin;
        $this->superAdmin = $superAdmin;
        $this->active = $active;
    }

    private function isSameAs(self $other): bool
    {
        return $this === $other || (null !== $this->id && $this->id === $other->id);
    }

    public function auditProjectId(): ?int
    {
        return null;
    }
}
