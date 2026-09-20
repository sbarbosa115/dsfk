<?php

namespace App\DataFixtures;

use App\Entity\Attachment;
use App\Entity\BudgetLine;
use App\Entity\Category;
use App\Entity\Expense;
use App\Entity\FundMovement;
use App\Entity\Milestone;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\Reimbursement;
use App\Entity\Stage;
use App\Entity\User;
use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Enum\PaidFrom;
use App\Enum\PaymentMethod;
use App\Enum\ProjectRole;
use App\Enum\ProjectStatus;
use App\Service\LedgerService;
use App\Service\PettyCashService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Demo data for local testing: three projects at different points of their life, so every
 * screen has something to show and every role has something to do.
 *
 * Load with `make seed` (dev only). It purges the database first.
 *
 * Money is in minor units; COP has no decimals, so 1 unit = 1 peso.
 */
class AppFixtures extends Fixture
{
    public const PASSWORD = 'demo1234';

    private ObjectManager $em;

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly PettyCashService $pettyCash,
        private readonly LedgerService $ledger,
        #[Autowire('%app.upload_dir%')] private readonly string $uploadDir,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->em = $manager;

        $admin = $this->user('admin@demo.test', 'Sofía Restrepo', admin: true, superAdmin: true);
        $this->user('admin2@demo.test', 'Julián Mesa', admin: true);
        $pm = $this->user('pm@demo.test', 'Laura Gómez');
        $pm2 = $this->user('pm2@demo.test', 'Andrés Beltrán');
        $lead = $this->user('lider@demo.test', 'Carlos Ruiz');
        $lead2 = $this->user('lider2@demo.test', 'Ana Torres');
        $this->user('inactivo@demo.test', 'Pedro Salas', active: false);
        $manager->flush();

        $this->runningProject($admin, $pm, $lead, $lead2);
        $this->draftProject($pm2, $lead2);
        $this->awaitingApprovalProject($pm2, $lead);

        $manager->flush();
    }

    /**
     * Edificio Aurora: approved budget, money in, one stage finished and carried over,
     * one in progress, expenses in every status and a caja menor cycle already closed.
     */
    private function runningProject(User $admin, User $pm, User $lead, User $lead2): void
    {
        $project = $this->project('Edificio Aurora', 'Torre de 8 pisos en el barrio Laureles.', ProjectStatus::Active, '-8 months', '+4 months');
        $this->member($project, $pm, ProjectRole::ProjectManager);
        $this->member($project, $lead, ProjectRole::TeamLead);
        $this->member($project, $lead2, ProjectRole::TeamLead);

        $materials = $this->category($project, 'Materiales');
        $labour = $this->category($project, 'Nómina');
        $equipment = $this->category($project, 'Equipos');

        $foundation = $this->stage($project, 'Cimentación', 0, '-8 months', '-6 months');
        $structure = $this->stage($project, 'Estructura', 1, '-6 months', '-2 months');
        $masonry = $this->stage($project, 'Mampostería', 2, '-2 months', '+1 month');
        $finishes = $this->stage($project, 'Acabados', 3, '+1 month', '+4 months');

        $this->line($foundation, $materials, 'Concreto de 3000 psi', 'm3', '120', 480_000, 0);
        $this->line($foundation, $labour, 'Cuadrilla de cimentación', 'mes', '2', 9_600_000, 1);
        $this->line($structure, $materials, 'Acero de refuerzo', 'kg', '18000', 5_200, 0);
        $this->line($structure, $materials, 'Concreto estructural', 'm3', '220', 495_000, 1);
        $this->line($structure, $labour, 'Cuadrilla de estructura', 'mes', '4', 11_500_000, 2);
        $this->line($structure, $equipment, 'Alquiler de formaleta', 'mes', '4', 3_200_000, 3);
        $this->line($masonry, $materials, 'Ladrillo estructural', 'un', '45000', 1_450, 0);
        $this->line($masonry, $labour, 'Cuadrilla de mampostería', 'mes', '3', 8_900_000, 1);
        $this->line($finishes, $materials, 'Enchape y pintura', 'global', '1', 62_000_000, 0);
        $this->line($finishes, $labour, 'Cuadrilla de acabados', 'mes', '3', 9_400_000, 1);

        $this->milestone($foundation, 'Excavación', 40, '-7 months', completedAt: '-7 months', by: $pm);
        $this->milestone($foundation, 'Vaciado de zapatas', 60, '-6 months', completedAt: '-6 months', by: $pm);
        $this->milestone($structure, 'Placa de primer piso', 30, '-5 months', completedAt: '-5 months', by: $pm);
        $this->milestone($structure, 'Placa de cuarto piso', 40, '-3 months', completedAt: '-3 months', by: $pm);
        $this->milestone($structure, 'Cubierta', 30, '-1 month');
        $this->milestone($masonry, 'Muros pisos 1-4', 50, '+15 days');
        $this->milestone($masonry, 'Muros pisos 5-8', 50, '+1 month');
        $this->milestone($finishes, 'Enchapes', 50, '+3 months');
        $this->milestone($finishes, 'Entrega final', 50, '+4 months');

        $budget = $project->getBudget();
        $budget->setContingency(38_000_000);
        $budget->submit($pm);
        $budget->approve($admin);
        $this->em->flush();

        // Money in: one deposit per stage that has started, plus caja menor and contingencia.
        $this->deposit($project, $pm, '-8 months', PaymentMethod::Transfer, 'BANCOLOMBIA-88120', [
            [LedgerAccount::Stage, 70_000_000, $foundation, null],
            [LedgerAccount::PettyCash, 4_000_000, null, null],
            [LedgerAccount::Contingency, 20_000_000, null, null],
        ]);
        $this->deposit($project, $pm, '-5 months', PaymentMethod::Transfer, 'BANCOLOMBIA-91455', [
            [LedgerAccount::Stage, 150_000_000, $structure, null],
            [LedgerAccount::PettyCash, 3_000_000, null, null],
        ]);
        $this->em->flush();

        $foundation->start($this->date('-8 months'));
        $structure->start($this->date('-6 months'));

        // Spending on the finished stage, then it is completed and the surplus carried over.
        $this->expense($project, $pm, $foundation, $materials, '-7 months', 52_000_000, 'Concreto premezclado', 'Cementos del Valle', 'FV-2201', PaidFrom::Stage);
        $this->expense($project, $pm, $foundation, $labour, '-6 months', 9_600_000, 'Nómina cimentación', null, null, PaidFrom::Stage);
        $this->em->flush();
        $this->ledger->completeStage($foundation, $this->date('-6 months'), $pm);
        $this->em->flush();

        // The stage in progress: a contingency draw and a mix of expense states.
        $draw = new FundMovement($project, MovementType::ContingencyDraw, $this->date('-4 months'), $pm, 'Ajuste por alza del acero');
        $draw->addEntry(LedgerAccount::Contingency, -6_000_000);
        $draw->addEntry(LedgerAccount::Stage, 6_000_000, $structure);
        $this->em->persist($draw);
        $this->em->flush();

        $this->expense($project, $pm, $structure, $materials, '-4 months', 93_600_000, 'Acero de refuerzo', 'Aceros SA', 'FV-7781', PaidFrom::Stage);
        $this->expense($project, $pm, $structure, $labour, '-3 months', 23_000_000, 'Nómina estructura (2 meses)', null, null, PaidFrom::Stage);
        $this->expense($project, $pm, $structure, $equipment, '-2 months', 320_000, 'Transporte de formaleta', 'Transportes Gil', 'FV-114', PaidFrom::PettyCash);

        // Team lead expenses: one reimbursed, one waiting for the PM, one rejected,
        // one above the limit waiting for the admin.
        $reimbursed = $this->expense($project, $lead, $structure, $materials, '-2 months', 180_000, 'Guantes y tapabocas', 'Ferretería Central', 'FV-9001', PaidFrom::OutOfPocket, approveBy: $pm);
        $this->reimburse($project, $pm, [$reimbursed], '-2 months');

        $this->expense($project, $lead, $structure, $materials, '-10 days', 240_000, 'Tornillería y anclajes', 'Ferretería Central', null, PaidFrom::OutOfPocket, receipt: false);
        $this->expense($project, $lead2, $structure, $materials, '-6 days', 95_000, 'Cinta y sellante', null, null, PaidFrom::OutOfPocket, rejectBy: $pm, reason: 'Falta la factura del proveedor.');
        $this->expense($project, $lead, $structure, $equipment, '-3 days', 1_250_000, 'Alquiler de andamios', 'Andamios JR', 'FV-4420', PaidFrom::OutOfPocket, pmApproveBy: $pm);
        $this->em->flush();

        // A caja menor cycle reviewed end to end, and a new one already in use.
        $cycle = $this->pettyCash->close($project, $pm, 'Cierre mensual de obra.');
        $cycle->signOff($admin);
        $this->em->flush();

        $this->deposit($project, $pm, '-1 month', PaymentMethod::Cash, null, [[LedgerAccount::PettyCash, 2_500_000, null, null]]);
        $this->expense($project, $pm, $structure, $materials, '-20 days', 410_000, 'Insumos varios de obra', 'Ferretería Central', 'FV-9120', PaidFrom::PettyCash);
        $this->em->flush();
    }

    /** Conjunto Sauces: a budget still being written, deliberately incomplete. */
    private function draftProject(User $pm, User $lead): void
    {
        $project = $this->project('Conjunto Sauces', 'Cuatro casas en Rionegro.', ProjectStatus::Draft, '+1 month', '+10 months');
        $this->member($project, $pm, ProjectRole::ProjectManager);
        $this->member($project, $lead, ProjectRole::TeamLead);

        $materials = $this->category($project, 'Materiales');
        $labour = $this->category($project, 'Nómina');

        $site = $this->stage($project, 'Preliminares', 0, '+1 month', '+2 months');
        $build = $this->stage($project, 'Obra gris', 1, '+2 months', '+7 months');
        $this->stage($project, 'Acabados', 2, '+7 months', '+10 months');

        $this->line($site, $labour, 'Cerramiento y campamento', 'global', '1', 18_000_000, 0);
        $this->line($build, $materials, 'Materiales obra gris', 'global', '1', 210_000_000, 0);

        // Weights do not add up to 100% and one stage has no lines: the plan screen
        // will list exactly what is missing before it can be submitted.
        $this->milestone($site, 'Campamento listo', 60, '+2 months');
        $this->milestone($build, 'Placa de contrapiso', 40, '+4 months');

        $project->getBudget()->setContingency(15_000_000);
    }

    /** Bodega Norte: budget submitted, waiting for an admin to approve or return it. */
    private function awaitingApprovalProject(User $pm, User $lead): void
    {
        $project = $this->project('Bodega Norte', 'Bodega industrial de 1.200 m2.', ProjectStatus::Draft, '+2 weeks', '+8 months');
        $this->member($project, $pm, ProjectRole::ProjectManager);
        $this->member($project, $lead, ProjectRole::TeamLead);

        $materials = $this->category($project, 'Materiales');
        $labour = $this->category($project, 'Nómina');

        $slab = $this->stage($project, 'Placa y pisos', 0, '+2 weeks', '+3 months');
        $shed = $this->stage($project, 'Cubierta metálica', 1, '+3 months', '+8 months');

        $this->line($slab, $materials, 'Concreto de piso', 'm3', '300', 470_000, 0);
        $this->line($slab, $labour, 'Cuadrilla de pisos', 'mes', '2', 8_200_000, 1);
        $this->line($shed, $materials, 'Estructura metálica', 'kg', '30000', 7_800, 0);
        $this->line($shed, $labour, 'Montaje', 'mes', '3', 10_500_000, 1);

        $this->milestone($slab, 'Subbase compactada', 50, '+1 month');
        $this->milestone($slab, 'Placa vaciada', 50, '+3 months');
        $this->milestone($shed, 'Columnas montadas', 50, '+5 months');
        $this->milestone($shed, 'Cubierta instalada', 50, '+8 months');

        $budget = $project->getBudget();
        $budget->setContingency(22_000_000);
        $budget->submit($pm);
    }

    // ---------------------------------------------------------------- builders

    private function user(string $email, string $name, bool $admin = false, bool $superAdmin = false, bool $active = true): User
    {
        $user = new User($email, $name);
        $user->setAdmin($admin);
        $user->setSuperAdmin($superAdmin);
        $user->setActive($active);
        $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));
        $this->em->persist($user);

        return $user;
    }

    private function project(string $name, string $description, ProjectStatus $status, string $start, string $end): Project
    {
        $project = new Project($name, 'COP');
        $project->setDescription($description);
        $project->setStatus($status);
        $project->setPlannedStart($this->date($start));
        $project->setPlannedEnd($this->date($end));
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    private function member(Project $project, User $user, ProjectRole $role): void
    {
        $member = new ProjectMember($project, $user, $role);
        $project->addMember($member);
        $this->em->persist($member);
    }

    private function category(Project $project, string $name): Category
    {
        $category = new Category($project, $name);
        $project->addCategory($category);
        $this->em->persist($category);

        return $category;
    }

    private function stage(Project $project, string $name, int $position, string $start, string $end): Stage
    {
        $stage = new Stage($project, $name, $position);
        $stage->setPlannedStart($this->date($start));
        $stage->setPlannedEnd($this->date($end));
        $project->addStage($stage);
        $this->em->persist($stage);

        return $stage;
    }

    private function line(Stage $stage, Category $category, string $description, string $unit, string $quantity, int $unitPrice, int $position): void
    {
        $line = new BudgetLine($stage, $category, $description, $unit, $quantity, $unitPrice, $position);
        $stage->addBudgetLine($line);
        $this->em->persist($line);
    }

    private function milestone(Stage $stage, string $name, int $weight, string $planned, ?string $completedAt = null, ?User $by = null): void
    {
        $milestone = new Milestone($stage, $name, $weight, $stage->getMilestones()->count());
        $milestone->setPlannedDate($this->date($planned));
        if (null !== $completedAt && null !== $by) {
            $milestone->complete($this->date($completedAt), $by, null);
        }
        $stage->addMilestone($milestone);
        $this->em->persist($milestone);
    }

    /**
     * @param list<array{0: LedgerAccount, 1: int, 2: ?Stage, 3: ?Category}> $allocations
     */
    private function deposit(Project $project, User $by, string $date, PaymentMethod $method, ?string $reference, array $allocations): void
    {
        $movement = new FundMovement($project, MovementType::Deposit, $this->date($date), $by);
        $movement->setPayment($method, $reference);
        foreach ($allocations as [$account, $amount, $stage, $category]) {
            $movement->addEntry($account, $amount, $stage, $category);
        }
        $this->pettyCash->assign($movement);
        $this->em->persist($movement);
        $this->attach($project, $by, movement: $movement);
        $this->em->flush();
    }

    /**
     * Mirrors ExpenseService: the expense plus the ledger movement that pays for it.
     * Out-of-pocket expenses take no money until they are reimbursed.
     */
    private function expense(
        Project $project,
        User $by,
        Stage $stage,
        Category $category,
        string $date,
        int $amount,
        string $description,
        ?string $supplier,
        ?string $invoice,
        PaidFrom $paidFrom,
        ?User $approveBy = null,
        ?User $pmApproveBy = null,
        ?User $rejectBy = null,
        ?string $reason = null,
        bool $receipt = true,
    ): Expense {
        $expense = new Expense($project, $paidFrom, $by);
        $expense->setDetails($stage, $category, $this->date($date), $amount, $description, $supplier, $invoice);
        $this->em->persist($expense);
        $expense->record('CREATED', $by);

        if ($receipt) {
            $this->attach($project, $by, expense: $expense);
        }

        if (PaidFrom::OutOfPocket !== $paidFrom) {
            $movement = new FundMovement($project, MovementType::Expense, $this->date($date), $by, $description);
            if (PaidFrom::Stage === $paidFrom) {
                $movement->addEntry(LedgerAccount::Stage, -$amount, $stage, $category);
            } else {
                $movement->addEntry(LedgerAccount::PettyCash, -$amount);
                $this->pettyCash->assign($movement);
            }
            $this->em->persist($movement);
            $expense->linkMovement($movement);
            $this->em->flush();

            return $expense;
        }

        if (null !== $approveBy) {
            $expense->approve();
            $expense->record('APPROVED', $approveBy);
        } elseif (null !== $pmApproveBy) {
            $expense->markPmApproved();
            $expense->record('PM_APPROVED', $pmApproveBy);
        } elseif (null !== $rejectBy) {
            $expense->reject((string) $reason);
            $expense->record('REJECTED', $rejectBy, $reason);
        }

        return $expense;
    }

    /** @param list<Expense> $expenses */
    private function reimburse(Project $project, User $by, array $expenses, string $date): void
    {
        $total = array_sum(array_map(static fn (Expense $e) => $e->getAmount(), $expenses));

        $movement = new FundMovement($project, MovementType::Reimbursement, $this->date($date), $by, 'Reembolso a líderes de equipo');
        $movement->addEntry(LedgerAccount::PettyCash, -$total);
        $this->pettyCash->assign($movement);
        $this->em->persist($movement);

        $reimbursement = new Reimbursement($project, $movement, PaymentMethod::Transfer, 'NEQUI-3310');
        $this->em->persist($reimbursement);
        foreach ($expenses as $expense) {
            $expense->markReimbursed($reimbursement);
            $reimbursement->getExpenses()->add($expense);
            $expense->record('REIMBURSED', $by);
        }
        $this->em->flush();
    }

    /** A real (tiny) PNG on disk, so the receipt can be opened and the expense approved. */
    private function attach(Project $project, User $by, ?FundMovement $movement = null, ?Expense $expense = null): void
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $storedName = \sprintf('%d/%s.png', $project->getId(), bin2hex(random_bytes(16)));
        $path = $this->uploadDir.'/'.$storedName;
        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0o775, true);
        }
        file_put_contents($path, $bytes);

        $attachment = new Attachment($project, $storedName, 'soporte.png', 'image/png', \strlen((string) $bytes), $by);
        $movement?->getAttachments()->add($attachment);
        $expense?->getAttachments()->add($attachment);
        $movement && $attachment->attachTo($movement);
        $expense && $attachment->attachToExpense($expense);
        $this->em->persist($attachment);
    }

    private function date(string $modifier): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today '.$modifier);
    }
}
