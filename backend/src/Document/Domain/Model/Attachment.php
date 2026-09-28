<?php

declare(strict_types=1);

namespace App\Document\Domain\Model;

use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Model\References;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An uploaded file: a proof of deposit, later an expense receipt. Stored outside the web root under a random name
 * and served only through an endpoint that checks access. What it is attached to lives in other contexts.
 */
#[ORM\Entity]
#[ORM\Table(name: 'attachment')]
class Attachment
{
    public const MAX_SIZE = 10 * 1024 * 1024;

    /** Accepted types (by content, never by the name the browser sent) and the extension they are stored with. */
    public const TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** A Finance movement (a proof of deposit). */
    #[ORM\Column(name: 'movement_id', nullable: true)]
    #[References('fund_movement', onDelete: 'CASCADE')]
    private ?int $movementId = null;

    /** An expense (its receipts). */
    #[ORM\Column(name: 'expense_id', nullable: true)]
    #[References('expense', onDelete: 'CASCADE')]
    private ?int $expenseId = null;

    #[ORM\Column(length: 255)]
    private string $originalName;

    private function __construct(
        #[ORM\Column(name: 'project_id')]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
        /** "<project>/<random>.<ext>" inside the upload directory. */
        #[ORM\Column(length: 100, unique: true)]
        private string $storedName,
        string $originalName,
        #[ORM\Column(length: 100)]
        private string $mimeType,
        #[ORM\Column]
        private int $size,
        #[ORM\Column(name: 'uploaded_by_id')]
        #[References('user')]
        private int $uploadedById,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
    ) {
        $name = trim((string) preg_replace('/[\/\\\\\x00-\x1F\x7F]/u', '_', $originalName));
        $this->originalName = mb_substr('' === $name ? 'archivo' : $name, 0, 255);
    }

    /**
     * Checks the file against the size limit and the accepted types, and names it.
     *
     * @param string $mimeType sniffed from the content
     * @param string $random   hex name part, from a secure random source
     *
     * @throws InvalidValue on the "file" field
     */
    public static function accept(int $projectId, string $originalName, string $mimeType, int $size, string $random, int $by, \DateTimeImmutable $now): self
    {
        if ($size <= 0) {
            throw InvalidValue::field('file', 'The file is empty.');
        }
        if ($size > self::MAX_SIZE) {
            throw InvalidValue::field('file', 'The file is larger than 10 MB.');
        }
        $extension = self::TYPES[$mimeType] ?? throw InvalidValue::field('file', 'File type not allowed. Use PDF, JPG, PNG, WEBP or HEIC.');

        return new self($projectId, \sprintf('%d/%s.%s', $projectId, $random, $extension), $originalName, $mimeType, $size, $by, $now);
    }

    public function attachToMovement(int $movementId): void
    {
        $this->movementId = $movementId;
    }

    public function attachToExpense(int $expenseId): void
    {
        $this->expenseId = $expenseId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getMovementId(): ?int
    {
        return $this->movementId;
    }

    public function getExpenseId(): ?int
    {
        return $this->expenseId;
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

    public function getUploadedById(): int
    {
        return $this->uploadedById;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
