<?php

namespace App\Controller;

use App\Dto\CloseCycleInput;
use App\Entity\Expense;
use App\Entity\FundMovement;
use App\Entity\PettyCashCycle;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\CycleStatus;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Security\ProjectVoter;
use App\Service\Alerts;
use App\Service\MoneyConverter;
use App\Service\PettyCashService;
use App\Service\ProjectAccess;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PettyCashController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PettyCashService $pettyCash,
    ) {
    }

    #[Route('/api/projects/{id}/petty-cash', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW_FINANCIALS, 'project')]
    public function show(Project $project, #[CurrentUser] User $user, ProjectAccess $access): JsonResponse
    {
        $current = $this->pettyCash->currentCycle($project);
        $this->em->flush();
        $cycles = $this->em->getRepository(PettyCashCycle::class)->findBy(['project' => $project], ['number' => 'DESC']);
        $role = $access->role($project, $user);

        return $this->json([
            'balance' => MoneyConverter::toMajor($this->pettyCash->balance($project), $project->getCurrency()),
            'current' => $this->cycle($current, true),
            'history' => array_values(array_map(fn (PettyCashCycle $c) => $this->cycle($c, false), array_filter($cycles, static fn ($c) => !$c->isOpen()))),
            'unsignedCount' => \count(array_filter($cycles, static fn ($c) => CycleStatus::Closed === $c->getStatus())),
            'permissions' => [
                'close' => \in_array($role, [ProjectAccess::ADMIN, 'PROJECT_MANAGER'], true),
                'signOff' => ProjectAccess::ADMIN === $role,
            ],
        ]);
    }

    #[Route('/api/petty-cash-cycles/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(PettyCashCycle $cycle): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW_FINANCIALS, $cycle->getProject());

        return $this->json($this->cycle($cycle, true));
    }

    #[Route('/api/projects/{id}/petty-cash/close', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(ProjectVoter::VIEW_FINANCIALS, 'project')]
    public function close(Project $project, #[MapRequestPayload] CloseCycleInput $input, #[CurrentUser] User $user, ProjectAccess $access, Alerts $alerts): JsonResponse
    {
        if (!$access->isManager($project, $user)) {
            throw new AccessDeniedHttpException();
        }
        $cycle = $this->pettyCash->close($project, $user, $input->note);
        $alerts->cycleClosed($cycle);
        $this->em->flush();

        return $this->json($this->cycle($cycle, true));
    }

    #[Route('/api/petty-cash-cycles/{id}/sign-off', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function signOff(PettyCashCycle $cycle, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProjectVoter::ADMINISTER, $cycle->getProject());
        $cycle->signOff($user);
        $this->em->flush();

        return $this->json($this->cycle($cycle, true));
    }

    /**
     * Cycle summary: opening balance, top-ups, spending, reimbursements and closing balance.
     *
     * @return array<string, mixed>
     */
    private function cycle(PettyCashCycle $cycle, bool $withMovements): array
    {
        $project = $cycle->getProject();
        $money = static fn (int $minor) => MoneyConverter::toMajor($minor, $project->getCurrency());
        /** @var list<FundMovement> $movements */
        $movements = $this->em->getRepository(FundMovement::class)->findBy(['pettyCashCycle' => $cycle], ['date' => 'ASC', 'id' => 'ASC']);

        $totals = ['DEPOSIT' => 0, 'EXPENSE' => 0, 'REIMBURSEMENT' => 0];
        $lines = [];
        foreach ($movements as $m) {
            $amount = 0;
            foreach ($m->getEntries() as $entry) {
                if (LedgerAccount::PettyCash === $entry->getAccount()) {
                    $amount += $entry->getAmount();
                }
            }
            if (!$m->isVoided()) {
                $totals[$m->getType()->value] = ($totals[$m->getType()->value] ?? 0) + $amount;
            }
            if ($withMovements) {
                $lines[] = [
                    'id' => $m->getId(),
                    'type' => $m->getType()->value,
                    'date' => $m->getDate()->format('Y-m-d'),
                    'amount' => $money($amount),
                    'description' => $this->describe($m),
                    'user' => $m->getCreatedBy()->getFullName(),
                    'voided' => $m->isVoided(),
                    'attachments' => $this->attachmentsOf($m),
                ];
            }
        }
        $computed = $cycle->getOpeningBalance() + array_sum($totals);

        $data = [
            'id' => $cycle->getId(),
            'number' => $cycle->getNumber(),
            'status' => $cycle->getStatus()->value,
            'openedAt' => $cycle->getOpenedAt()->format(\DATE_ATOM),
            'closedAt' => $cycle->getClosedAt()?->format(\DATE_ATOM),
            'closedBy' => $cycle->getClosedBy()?->getFullName(),
            'closingNote' => $cycle->getClosingNote(),
            'signedOffAt' => $cycle->getSignedOffAt()?->format(\DATE_ATOM),
            'signedOffBy' => $cycle->getSignedOffBy()?->getFullName(),
            'openingBalance' => $money($cycle->getOpeningBalance()),
            'topUps' => $money($totals['DEPOSIT']),
            'spent' => $money(-$totals['EXPENSE']),
            'reimbursed' => $money(-$totals['REIMBURSEMENT']),
            'closingBalance' => $money($cycle->getClosingBalance() ?? $computed),
        ];
        if ($withMovements) {
            $data['movements'] = $lines;
        }

        return $data;
    }

    private function describe(FundMovement $m): string
    {
        if (MovementType::Reimbursement === $m->getType()) {
            $expenses = $this->em->getRepository(Expense::class)->createQueryBuilder('e')
                ->join('e.reimbursement', 'r')->where('r.movement = :m')->setParameter('m', $m)
                ->getQuery()->getResult();

            return 'Reembolso: '.implode(', ', array_map(static fn (Expense $e) => $e->getPaidBy()->getFullName().' – '.$e->getDescription(), $expenses));
        }

        return $m->getNote() ?? '';
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function attachmentsOf(FundMovement $m): array
    {
        $attachments = $m->getAttachments()->toArray();
        if (MovementType::Expense === $m->getType()) {
            $expense = $this->em->getRepository(Expense::class)->findOneBy(['movement' => $m]);
            $attachments = $expense ? $expense->getAttachments()->toArray() : [];
        }

        return array_map(static fn ($a) => ['id' => $a->getId(), 'name' => $a->getOriginalName()], $attachments);
    }
}
