<?php

namespace App\Entity;

use App\Enum\PaymentMethod;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The PM paying back one or more approved Team Lead expenses from the caja menor.
 */
#[ORM\Entity]
class Reimbursement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private FundMovement $movement;

    #[ORM\Column(length: 20, enumType: PaymentMethod::class)]
    private PaymentMethod $method;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference;

    /** @var Collection<int, Expense> */
    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'reimbursement')]
    private Collection $expenses;

    public function __construct(Project $project, FundMovement $movement, PaymentMethod $method, ?string $reference)
    {
        $this->project = $project;
        $this->movement = $movement;
        $this->method = $method;
        $this->reference = null === $reference || '' === trim($reference) ? null : trim($reference);
        $this->expenses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovement(): FundMovement
    {
        return $this->movement;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * @return Collection<int, Expense>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }
}
