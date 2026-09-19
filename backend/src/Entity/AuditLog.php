<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Append-only record of changes to audited entities. Written with plain SQL by
 * AuditListener after each flush, never through the ORM.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Index(name: 'idx_audit_project', fields: ['projectId', 'createdAt'])]
#[ORM\Index(name: 'idx_audit_entity', fields: ['entityType', 'entityId'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private string|int $id;

    #[ORM\Column(nullable: true)]
    private ?int $projectId = null;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $userName = null;

    /** create | update | delete */
    #[ORM\Column(length: 10)]
    private string $action;

    #[ORM\Column(length: 60)]
    private string $entityType;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    /** field => [old, new] */
    #[ORM\Column(type: Types::JSON)]
    private array $changes = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
