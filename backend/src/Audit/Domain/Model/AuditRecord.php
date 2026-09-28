<?php

declare(strict_types=1);

namespace App\Audit\Domain\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One change to an audited record: who (and, while an Admin views the app as someone else, via whom), what, when.
 * Append-only: written with plain SQL by the trail listener, never changed.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_project', columns: ['project_id', 'created_at'])]
#[ORM\Index(name: 'idx_audit_entity', columns: ['entity_type', 'entity_id'])]
class AuditRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private int $id;

    /**
     * @param array<string, mixed> $changes field => [old, new]
     */
    public function __construct(
        #[ORM\Column(length: 10)]
        private string $action,
        #[ORM\Column(length: 60)]
        private string $entityType,
        #[ORM\Column(nullable: true)]
        private ?int $entityId,
        #[ORM\Column(type: Types::JSON)]
        private array $changes,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\Column(nullable: true)]
        private ?int $projectId = null,
        #[ORM\Column(nullable: true)]
        private ?int $userId = null,
        #[ORM\Column(length: 150, nullable: true)]
        private ?string $userName = null,
    ) {
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
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

    /**
     * @return array<string, mixed>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
