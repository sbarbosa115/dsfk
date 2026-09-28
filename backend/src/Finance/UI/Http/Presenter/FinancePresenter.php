<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Presenter;

use App\Document\Application\Query\AttachmentQueries;
use App\Document\Application\Query\AttachmentView;
use App\Finance\Application\Port\Currencies;
use App\Finance\Application\Port\FundedPlan;
use App\Finance\Application\Port\People;
use App\Finance\Application\Port\PlanStage;
use App\Finance\Application\Port\Spending;
use App\Finance\Domain\Model\Balances;
use App\Finance\Domain\Model\FundMovement;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Model\LedgerEntry;
use App\Finance\Domain\Model\MovementType;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Finance\UI\Http\Output\AttachmentOutput;
use App\Finance\UI\Http\Output\CategorySpendingOutput;
use App\Finance\UI\Http\Output\ContingencyOutput;
use App\Finance\UI\Http\Output\FinanceOutput;
use App\Finance\UI\Http\Output\FinancePermissionsOutput;
use App\Finance\UI\Http\Output\FinancePersonOutput;
use App\Finance\UI\Http\Output\FinanceTotalsOutput;
use App\Finance\UI\Http\Output\LedgerEntryOutput;
use App\Finance\UI\Http\Output\MovementOutput;
use App\Finance\UI\Http\Output\PettyCashSummaryOutput;
use App\Finance\UI\Http\Output\StageFundingOutput;
use App\Finance\UI\Http\Output\VoidOutput;
use App\Shared\Domain\Money\MinorUnits;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;

/** The funding picture of a project and its movements, with names resolved and amounts in major units. */
final readonly class FinancePresenter
{
    public function __construct(
        private LedgerRepository $ledger,
        private FundedPlan $plan,
        private Currencies $currencies,
        private Spending $spending,
        private People $people,
        private AttachmentQueries $attachments,
        private ProjectGuard $guard,
    ) {
    }

    public function summary(int $projectId): FinanceOutput
    {
        $currency = $this->currencies->of($projectId);
        $money = static fn (int $minor): string => MinorUnits::toMajor($minor, $currency);
        $b = $this->ledger->balances($projectId);
        $approved = $this->plan->isApproved($projectId);
        $admin = $this->guard->allows(ProjectPermission::ADMINISTER, $projectId);
        $spending = $this->spending->byStageAndCategory($projectId);
        $plannedStages = $this->plan->stages($projectId);

        $stages = [];
        $deposited = 0;
        $totalSpent = 0;
        $stagesAvailable = 0;
        $stagesBudget = 0;
        foreach ($plannedStages as $i => $stage) {
            $key = Balances::key(LedgerAccount::Stage, $stage->id);
            $dep = $b->in($key, MovementType::Deposit);
            $draws = $b->in($key, MovementType::ContingencyDraw);
            $carriedIn = $b->in($key, MovementType::Carryover);
            $received = $dep + $draws + $carriedIn;
            $available = $b->balance($key);
            $spent = array_sum($spending[$stage->id] ?? []);
            $deposited += $dep;
            $stagesAvailable += $available;
            $totalSpent += $spent;
            $stagesBudget += $stage->budget;

            $stages[] = new StageFundingOutput(
                $stage->id,
                $stage->name,
                $stage->status,
                $money($stage->budget),
                $money($dep),
                $money($draws),
                $money($carriedIn),
                $money($b->out($key, MovementType::Carryover)),
                $money($received),
                $money($available),
                $money(max(0, $received - $stage->budget)),
                $money($spent),
                $money($stage->budget - $spent),
                self::share($spent, $stage->budget),
                MinorUnits::basisPoints($received, $stage->budget),
                $stage->isCompleted() ? null : self::nextOpen(\array_slice($plannedStages, $i + 1))?->name,
            );
        }

        $pc = Balances::key(LedgerAccount::PettyCash);
        $ct = Balances::key(LedgerAccount::Contingency);
        $deposited += $b->in($pc, MovementType::Deposit) + $b->in($ct, MovementType::Deposit);
        $contingencyBudget = $this->plan->contingency($projectId);

        $spentByCategory = [];
        foreach ($spending as $byCategory) {
            foreach ($byCategory as $categoryId => $amount) {
                $spentByCategory[$categoryId] = ($spentByCategory[$categoryId] ?? 0) + $amount;
            }
        }
        $categories = [];
        foreach ($this->plan->categories($projectId) as $category) {
            $spent = $spentByCategory[$category->id] ?? 0;
            $categories[] = new CategorySpendingOutput($category->id, $category->name, $money($category->budget), $money($spent), self::share($spent, $category->budget));
        }

        return new FinanceOutput(
            $currency,
            $approved,
            new FinancePermissionsOutput(deposit: $admin && $approved, void: $admin, drawContingency: $admin && $approved, completeStages: $admin && $approved),
            new FinanceTotalsOutput(
                $money($stagesBudget + $contingencyBudget),
                $money($deposited),
                $money($totalSpent),
                $money($stagesAvailable),
                $money($b->balance($pc)),
                $money($b->balance($ct)),
            ),
            $stages,
            $categories,
            new ContingencyOutput(
                $money($contingencyBudget),
                $money($b->in($ct, MovementType::Deposit)),
                $money($b->in($ct, MovementType::Carryover)),
                $money($b->out($ct, MovementType::ContingencyDraw)),
                $money($b->balance($ct)),
            ),
            new PettyCashSummaryOutput($money($b->in($pc, MovementType::Deposit)), $money($b->balance($pc))),
        );
    }

    public function movement(FundMovement $movement): MovementOutput
    {
        return $this->movements($movement->getProjectId(), [$movement])[0];
    }

    /**
     * @param list<FundMovement> $movements all of one project
     *
     * @return list<MovementOutput>
     */
    public function movements(int $projectId, array $movements): array
    {
        if ([] === $movements) {
            return [];
        }
        $currency = $this->currencies->of($projectId);
        $money = static fn (int $minor): string => MinorUnits::toMajor($minor, $currency);
        $stageNames = array_column(array_map(static fn ($s) => ['id' => $s->id, 'name' => $s->name], $this->plan->stages($projectId)), 'name', 'id');
        $categoryNames = array_column(array_map(static fn ($c) => ['id' => $c->id, 'name' => $c->name], $this->plan->categories($projectId)), 'name', 'id');
        $people = [];
        foreach ($movements as $m) {
            $people[] = $m->getCreatedById();
            if (null !== $m->getVoidedById()) {
                $people[] = $m->getVoidedById();
            }
        }
        $names = $this->people->names(array_values(array_unique($people)));
        $files = $this->attachments->ofMovements(array_map(static fn (FundMovement $m): int => (int) $m->getId(), $movements));

        return array_map(static fn (FundMovement $m): MovementOutput => new MovementOutput(
            (int) $m->getId(),
            $m->getType()->value,
            $m->getDate()->format('Y-m-d'),
            $money($m->getAmount()),
            $m->getMethod()?->value,
            $m->getReference(),
            $m->getNote(),
            new FinancePersonOutput($m->getCreatedById(), $names[$m->getCreatedById()] ?? '—'),
            $m->getCreatedAt()->format(\DATE_ATOM),
            null === $m->getVoidedAt() ? null : new VoidOutput(
                $m->getVoidedAt()->format(\DATE_ATOM),
                $names[(int) $m->getVoidedById()] ?? '—',
                (string) $m->getVoidReason(),
            ),
            array_map(static fn (LedgerEntry $e): LedgerEntryOutput => new LedgerEntryOutput(
                $e->getAccount()->value,
                $e->getStageId(),
                null === $e->getStageId() ? null : ($stageNames[$e->getStageId()] ?? null),
                $e->getCategoryId(),
                null === $e->getCategoryId() ? null : ($categoryNames[$e->getCategoryId()] ?? null),
                $money($e->getAmount()),
            ), $m->getEntries()),
            array_map(static fn (AttachmentView $a): AttachmentOutput => new AttachmentOutput($a->id, $a->name, $a->mimeType, $a->size), $files[(int) $m->getId()] ?? []),
        ), $movements);
    }

    /** Basis points of the budget spent; spending without a budget counts as fully spent. */
    private static function share(int $spent, int $budget): int
    {
        if (0 === $budget) {
            return $spent > 0 ? 10000 : 0;
        }

        return MinorUnits::basisPoints($spent, $budget);
    }

    /**
     * @param list<PlanStage> $later
     */
    private static function nextOpen(array $later): ?PlanStage
    {
        foreach ($later as $stage) {
            if (!$stage->isCompleted()) {
                return $stage;
            }
        }

        return null;
    }
}
