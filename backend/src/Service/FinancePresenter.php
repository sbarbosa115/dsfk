<?php

namespace App\Service;

use App\Entity\Attachment;
use App\Entity\Expense;
use App\Entity\FundMovement;
use App\Entity\LedgerEntry;
use App\Entity\Project;
use App\Enum\ExpenseStatus;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Security\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Funding picture of a project: where the deposited money is, per stage, caja menor
 * and contingency, compared to the approved budget. Amounts are decimal strings.
 */
class FinancePresenter
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Approved spending (counts against the budget) per stage and category, in minor units.
     *
     * @return array<int, array<int, int>> stageId => categoryId => amount
     */
    public function spending(Project $project): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(e.stage) AS stageId', 'IDENTITY(e.category) AS categoryId', 'SUM(e.amount) AS total')
            ->from(Expense::class, 'e')
            ->where('e.project = :project')
            ->andWhere('e.status IN (:spent)')
            ->groupBy('e.stage', 'e.category')
            ->setParameter('project', $project)
            ->setParameter('spent', [ExpenseStatus::Approved, ExpenseStatus::Reimbursed])
            ->getQuery()->getArrayResult();

        $spent = [];
        foreach ($rows as $row) {
            $spent[(int) $row['stageId']][(int) $row['categoryId']] = (int) $row['total'];
        }

        return $spent;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Project $project): array
    {
        $b = $this->ledger->balances($project);
        $money = static fn (int $minor): string => MoneyConverter::toMajor($minor, $project->getCurrency());
        $isAdmin = $this->security->isGranted(ProjectVoter::ADMINISTER, $project);
        $approved = $project->getBudget()->isApproved();

        $spending = $this->spending($project);
        $stages = [];
        $deposited = 0;
        $totalSpent = 0;
        $stagesAvailable = 0;
        foreach ($project->getStages() as $stage) {
            $key = Balances::key(LedgerAccount::Stage, $stage->getId());
            $budget = $stage->getBudgetTotal();
            $dep = $b->in($key, MovementType::Deposit);
            $draws = $b->in($key, MovementType::ContingencyDraw);
            $carriedIn = $b->in($key, MovementType::Carryover);
            $received = $dep + $draws + $carriedIn;
            $available = $b->balance($key);
            $deposited += $dep;
            $stagesAvailable += $available;
            $spent = array_sum($spending[$stage->getId()] ?? []);
            $totalSpent += $spent;

            $stages[] = [
                'id' => $stage->getId(),
                'name' => $stage->getName(),
                'status' => $stage->getStatus()->value,
                'budget' => $money($budget),
                'deposited' => $money($dep),
                'contingencyDraws' => $money($draws),
                'carriedIn' => $money($carriedIn),
                'carriedOut' => $money($b->out($key, MovementType::Carryover)),
                'received' => $money($received),
                'available' => $money($available),
                'beyondBudget' => $money(max(0, $received - $budget)),
                'spent' => $money($spent),
                'remainingBudget' => $money($budget - $spent),
                // Basis points of the budget already spent (can exceed 10000).
                'executed' => $budget > 0 ? intdiv($spent * 10000, $budget) : ($spent > 0 ? 10000 : 0),
                // Basis points of the budget already received.
                'funded' => $budget > 0 ? intdiv($received * 10000, $budget) : 0,
                'nextStage' => $stage->isCompleted() ? null : $this->ledger->nextOpenStage($stage)?->getName(),
            ];
        }

        $pc = LedgerAccount::PettyCash->value;
        $ct = LedgerAccount::Contingency->value;
        $deposited += $b->in($pc, MovementType::Deposit) + $b->in($ct, MovementType::Deposit);

        return [
            'currency' => $project->getCurrency(),
            'budgetApproved' => $approved,
            'permissions' => [
                'deposit' => $isAdmin && $approved,
                'void' => $isAdmin,
                'drawContingency' => $isAdmin && $approved,
                'completeStages' => $isAdmin && $approved,
            ],
            'totals' => [
                'budget' => $money($this->budgetTotal($project)),
                'deposited' => $money($deposited),
                'spent' => $money($totalSpent),
                'stagesAvailable' => $money($stagesAvailable),
                'pettyCash' => $money($b->balance($pc)),
                'contingency' => $money($b->balance($ct)),
            ],
            'stages' => $stages,
            'categories' => $this->byCategory($project, $spending, $money),
            'contingency' => [
                'budgeted' => $money($project->getBudget()->getContingency()),
                'deposited' => $money($b->in($ct, MovementType::Deposit)),
                'carriedIn' => $money($b->in($ct, MovementType::Carryover)),
                'drawn' => $money($b->out($ct, MovementType::ContingencyDraw)),
                'balance' => $money($b->balance($ct)),
            ],
            'pettyCash' => [
                'deposited' => $money($b->in($pc, MovementType::Deposit)),
                'balance' => $money($b->balance($pc)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function movement(FundMovement $m): array
    {
        $currency = $m->getProject()->getCurrency();

        return [
            'id' => $m->getId(),
            'type' => $m->getType()->value,
            'date' => $m->getDate()->format('Y-m-d'),
            'amount' => MoneyConverter::toMajor($m->getAmount(), $currency),
            'method' => $m->getMethod()?->value,
            'reference' => $m->getReference(),
            'note' => $m->getNote(),
            'createdBy' => ['id' => $m->getCreatedBy()->getId(), 'fullName' => $m->getCreatedBy()->getFullName()],
            'createdAt' => $m->getCreatedAt()->format(\DATE_ATOM),
            'voided' => $m->isVoided() ? [
                'at' => $m->getVoidedAt()->format(\DATE_ATOM),
                'by' => $m->getVoidedBy()?->getFullName(),
                'reason' => $m->getVoidReason(),
            ] : null,
            'entries' => array_map(static fn (LedgerEntry $e) => [
                'account' => $e->getAccount()->value,
                'stageId' => $e->getStage()?->getId(),
                'stageName' => $e->getStage()?->getName(),
                'categoryId' => $e->getCategory()?->getId(),
                'categoryName' => $e->getCategory()?->getName(),
                'amount' => MoneyConverter::toMajor($e->getAmount(), $currency),
            ], $m->getEntries()->toArray()),
            'attachments' => array_map(static fn (Attachment $a) => [
                'id' => $a->getId(),
                'name' => $a->getOriginalName(),
                'mimeType' => $a->getMimeType(),
                'size' => $a->getSize(),
            ], $m->getAttachments()->toArray()),
        ];
    }

    /**
     * @param array<int, array<int, int>> $spending
     * @param \Closure(int): string        $money
     *
     * @return list<array<string, mixed>>
     */
    private function byCategory(Project $project, array $spending, \Closure $money): array
    {
        $budget = [];
        foreach ($project->getStages() as $stage) {
            foreach ($stage->getBudgetLines() as $line) {
                $id = $line->getCategory()->getId();
                $budget[$id] = ($budget[$id] ?? 0) + $line->getTotal();
            }
        }
        $spent = [];
        foreach ($spending as $byCategory) {
            foreach ($byCategory as $id => $amount) {
                $spent[$id] = ($spent[$id] ?? 0) + $amount;
            }
        }

        $rows = [];
        foreach ($project->getCategories() as $category) {
            $b = $budget[$category->getId()] ?? 0;
            $s = $spent[$category->getId()] ?? 0;
            $rows[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'budget' => $money($b),
                'spent' => $money($s),
                'executed' => $b > 0 ? intdiv($s * 10000, $b) : ($s > 0 ? 10000 : 0),
            ];
        }

        return $rows;
    }

    private function budgetTotal(Project $project): int
    {
        $total = $project->getBudget()->getContingency();
        foreach ($project->getStages() as $stage) {
            $total += $stage->getBudgetTotal();
        }

        return $total;
    }
}
