<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An uploaded file (deposit proof, later expense receipts). Stored outside the web
 * root and only served through a controller that checks permissions.
 */
#[ORM\Entity]
class Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?FundMovement $movement = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Expense $expense = null;

    /** Random file name inside the upload directory. */
    #[ORM\Column(length: 100, unique: true)]
    private string $storedName;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 100)]
    private string $mimeType;

    #[ORM\Column]
    private int $size;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $uploadedBy;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Project $project, string $storedName, string $originalName, string $mimeType, int $size, User $uploadedBy)
    {
        $this->project = $project;
        $this->storedName = $storedName;
        $this->originalName = mb_substr($originalName, 0, 255);
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->uploadedBy = $uploadedBy;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function attachTo(FundMovement $movement): void
    {
        $this->movement = $movement;
    }

    public function attachToExpense(Expense $expense): void
    {
        $this->expense = $expense;
    }

    public function getExpense(): ?Expense
    {
        return $this->expense;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getMovement(): ?FundMovement
    {
        return $this->movement;
    }

    public function getStoredName(): string
    {
        return $this->storedName;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
