<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Model\Audited;
use App\Shared\Domain\Model\References;
use Doctrine\ORM\Mapping as ORM;

/** A cost category of one project (Nómina, Materiales…): budget vs actual is broken down by it. */
#[ORM\Entity]
#[ORM\Table(name: 'category')]
#[ORM\UniqueConstraint(name: 'uniq_category_name', fields: ['projectId', 'name'])]
class Category implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    public function __construct(
        #[ORM\Column(name: 'project_id')]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
        string $name,
    ) {
        $this->rename($name);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        if ('' === trim($name)) {
            throw InvalidValue::field('name', 'Este valor no debería estar vacío.');
        }
        $this->name = trim($name);
    }

    public function isNamed(string $name): bool
    {
        return mb_strtolower($this->name) === mb_strtolower(trim($name));
    }

    public function auditProjectId(): ?int
    {
        return $this->projectId;
    }
}
