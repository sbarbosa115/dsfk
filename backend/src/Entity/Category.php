<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cost category defined per project (Payroll, Materials, Equipment...).
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_category_name', fields: ['project', 'name'])]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 100)]
    private string $name;

    public function __construct(Project $project, string $name)
    {
        $this->project = $project;
        $this->name = trim($name);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }
}
