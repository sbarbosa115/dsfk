<?php

namespace App\Entity;

use App\Enum\LedgerAccount;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Signed amount (minor units) added to or taken from one account by a movement.
 */
#[ORM\Entity]
#[ORM\Index(name: 'idx_entry_account', fields: ['project', 'account', 'stage'])]
class LedgerEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private FundMovement $movement;

    /** Denormalised from the movement to keep balance queries simple. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 20, enumType: LedgerAccount::class)]
    private LedgerAccount $account;

    #[ORM\ManyToOne]
    private ?Stage $stage;

    /** Optional earmark for reporting; balances are per stage, not per category. */
    #[ORM\ManyToOne]
    private ?Category $category;

    #[ORM\Column(type: Types::BIGINT)]
    private string|int $amount;

    public function __construct(FundMovement $movement, LedgerAccount $account, int $amount, ?Stage $stage = null, ?Category $category = null)
    {
        if ((LedgerAccount::Stage === $account) !== (null !== $stage)) {
            throw new \LogicException('Stage entries need a stage, other accounts must not have one.');
        }
        if (0 === $amount) {
            throw new \LogicException('Ledger entries cannot be zero.');
        }
        $this->movement = $movement;
        $this->project = $movement->getProject();
        $this->account = $account;
        $this->amount = $amount;
        $this->stage = $stage;
        $this->category = $category;
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

    public function getStage(): ?Stage
    {
        return $this->stage;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getAmount(): int
    {
        return (int) $this->amount;
    }
}
