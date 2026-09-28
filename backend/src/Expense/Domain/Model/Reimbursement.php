<?php

declare(strict_types=1);

namespace App\Expense\Domain\Model;

use App\Shared\Domain\Model\References;
use Doctrine\ORM\Mapping as ORM;

/** The PM (or an Admin) paying back approved Team Lead expenses from the caja menor, as one movement. */
#[ORM\Entity]
#[ORM\Table(name: 'reimbursement')]
class Reimbursement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference;

    public function __construct(
        #[ORM\Column(name: 'project_id')]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
        /** Finance's REIMBURSEMENT movement. */
        #[ORM\Column(name: 'movement_id', unique: true)]
        #[References('fund_movement')]
        private int $movementId,
        #[ORM\Column(length: 20, enumType: PayoutMethod::class)]
        private PayoutMethod $method,
        ?string $reference,
    ) {
        $this->reference = null === $reference || '' === trim($reference) ? null : trim($reference);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getMovementId(): int
    {
        return $this->movementId;
    }

    public function getMethod(): PayoutMethod
    {
        return $this->method;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }
}
