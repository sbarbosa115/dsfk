<?php

namespace App\Command;

use App\Entity\Expense;
use App\Entity\Milestone;
use App\Entity\PettyCashCycle;
use App\Entity\Project;
use App\Enum\CycleStatus;
use App\Enum\ExpenseStatus;
use App\Enum\PaidFrom;
use App\Enum\ProjectStatus;
use App\Service\Alerts;
use App\Service\Notifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Daily digest per active project (overdue milestones, pending approvals and
 * reimbursements, unsigned caja menor cycles) for the PM and the Admins.
 * Run once a day from cPanel cron.
 */
#[AsCommand(name: 'app:alerts:daily', description: 'Send the daily project digest emails.')]
class DailyAlertsCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Alerts $alerts,
        private readonly Notifier $notifier,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $today = new \DateTimeImmutable('today');
        $sent = 0;

        foreach ($this->em->getRepository(Project::class)->findBy(['status' => ProjectStatus::Active]) as $project) {
            $overdue = $this->em->createQueryBuilder()
                ->select('m')->from(Milestone::class, 'm')->join('m.stage', 's')
                ->where('s.project = :project')->andWhere('m.completedAt IS NULL')->andWhere('m.plannedDate < :today')
                ->setParameter('project', $project)->setParameter('today', $today)
                ->orderBy('m.plannedDate')->getQuery()->getResult();
            $pendingCount = $this->countExpenses($project, [ExpenseStatus::Submitted, ExpenseStatus::PmApproved]);
            $toReimburseCount = $this->em->getRepository(Expense::class)->count(['project' => $project, 'status' => ExpenseStatus::Approved, 'reimbursement' => null, 'paidFrom' => PaidFrom::OutOfPocket]);
            $unsignedCycles = $this->em->getRepository(PettyCashCycle::class)->count(['project' => $project, 'status' => CycleStatus::Closed]);

            if ([] === $overdue && 0 === $pendingCount + $toReimburseCount + $unsignedCycles) {
                continue;
            }
            $this->notifier->notify([...$this->alerts->admins(), $this->alerts->projectManager($project)], 'Resumen diario: '.$project->getName(), 'daily_digest', [
                'project' => $project,
                'overdue' => $overdue,
                'pendingCount' => $pendingCount,
                'toReimburseCount' => $toReimburseCount,
                'unsignedCycles' => $unsignedCycles,
            ]);
            ++$sent;
        }

        $io->success(\sprintf('%d project digest(s) queued.', $sent));

        return Command::SUCCESS;
    }

    /**
     * @param list<ExpenseStatus> $statuses
     */
    private function countExpenses(Project $project, array $statuses): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(e.id)')->from(Expense::class, 'e')
            ->where('e.project = :project')->andWhere('e.status IN (:statuses)')
            ->setParameter('project', $project)->setParameter('statuses', $statuses)
            ->getQuery()->getSingleScalarResult();
    }
}
