<?php

declare(strict_types=1);

namespace App\Finance\Domain\Model;

use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Model\Audited;
use App\Shared\Domain\Model\References;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** Signed amount (minor units) a movement adds to or takes from one account. */
#[ORM\Entity]
#[ORM\Table(name: 'ledger_entry')]
#[ORM\Index(name: 'idx_entry_account', columns: ['project_id', 'account', 'stage_id'])]
class LedgerEntry implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Copied from the movement to keep balance queries simple. */
    #[ORM\Column(name: 'project_id')]
    #[References('project', onDelete: 'CASCADE')]
    private int $projectId;

    #[ORM\Column(type: Types::BIGINT)]
    private int $amount;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'entries')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private FundMovement $movement,
        #[ORM\Column(length: 20, enumType: LedgerAccount::class)]
        private LedgerAccount $account,
        int $amount,
        /** Planning's stage: set on stage entries only. */
        #[ORM\Column(name: 'stage_id', nullable: true)]
        #[References('stage')]
        private ?int $stageId = null,
        /** Optional earmark for reporting; balances are per stage, not per category. */
        #[ORM\Column(name: 'category_id', nullable: true)]
        #[References('category')]
        private ?int $categoryId = null,
    ) {
        if ((LedgerAccount::Stage === $account) !== (null !== $stageId)) {
            throw InvalidValue::field('stageId', LedgerAccount::Stage === $account ? 'Choose the stage.' : 'Only stage entries have a stage.');
        }
        if (0 === $amount) {
            throw InvalidValue::field('amount', 'The amount cannot be zero.');
        }
        $this->projectId = $movement->getProjectId();
        $this->amount = $amount;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovement(): FundMovement
    {
        return $this->movement;
    }

    public function getAccount(): LedgerAccount
    {
        return $this->account;
    }

    public function getStageId(): ?int
    {
        return $this->stageId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getAmount(): int
    {
        return (int) $this->amount;
    }

    public function key(): string
    {
        return Balances::key($this->account, $this->stageId);
    }

    public function auditProjectId(): ?int
    {
        return $this->projectId;
    }
}
