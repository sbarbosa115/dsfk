<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Expense\Application\Command\AddReceipt;
use App\Expense\Application\Command\ApproveExpense;
use App\Expense\Application\Command\ExpenseDetails;
use App\Expense\Application\Command\RecordExpense;
use App\Expense\Application\Command\ReimburseExpenses;
use App\Expense\Application\Command\RejectExpense;
use App\Expense\Domain\Model\PaidFrom;
use App\Expense\Domain\Model\PayoutMethod;
use App\Finance\Application\Command\Allocation;
use App\Finance\Application\Command\CloseCycle;
use App\Finance\Application\Command\RecordDeposit;
use App\Finance\Application\Command\SignOffCycle;
use App\Finance\Domain\Model\LedgerAccount;
use App\Finance\Domain\Model\PaymentMethod;
use App\Identity\Application\Command\CreateFirstSuperAdmin;
use App\Identity\Application\Command\CreateUser;
use App\Planning\Application\Command\AddCategory;
use App\Planning\Application\Command\AddLine;
use App\Planning\Application\Command\AddMilestone;
use App\Planning\Application\Command\AddStage;
use App\Planning\Application\Command\ApproveBudget;
use App\Planning\Application\Command\CompleteMilestone;
use App\Planning\Application\Command\CompleteStage;
use App\Planning\Application\Command\SetContingency;
use App\Planning\Application\Command\StartStage;
use App\Planning\Application\Command\SubmitBudget;
use App\Planning\Domain\Repository\PlanRepository;
use App\Project\Application\Command\AssignMember;
use App\Project\Application\Command\CreateProject;
use App\Project\Domain\Model\ProjectRole;
use App\Project\Domain\Model\ProjectStatus;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\NewId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Psr\Clock\ClockInterface;

/**
 * Demo data for trying the app locally (`make seed`, dev only): three projects at different points of their life,
 * so every screen has something to show and every role something to do. Everything goes through the same commands
 * the API sends, so the data follows the same rules. Every account's password is `demo1234`.
 */
final class DemoFixtures extends Fixture
{
    public const PASSWORD = 'demo1234';

    private \DateTimeImmutable $today;

    public function __construct(private readonly CommandBus $bus, private readonly PlanRepository $plans, ClockInterface $clock)
    {
        $this->today = $clock->now()->setTime(0, 0);
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->id(new CreateFirstSuperAdmin('admin@demo.test', 'Sofía Restrepo', self::PASSWORD));
        $this->id(new CreateUser($admin, 'admin2@demo.test', 'Julián Mesa', self::PASSWORD, admin: true));
        $pm = $this->id(new CreateUser($admin, 'pm@demo.test', 'Laura Gómez', self::PASSWORD));
        $pm2 = $this->id(new CreateUser($admin, 'pm2@demo.test', 'Andrés Beltrán', self::PASSWORD));
        $lead = $this->id(new CreateUser($admin, 'lider@demo.test', 'Carlos Ruiz', self::PASSWORD));
        $lead2 = $this->id(new CreateUser($admin, 'lider2@demo.test', 'Ana Torres', self::PASSWORD));

        $this->torreNorte($admin, $pm, $lead, $lead2);
        $this->casaCampestre($admin, $pm2, $lead2);

        $bodega = $this->id(new CreateProject('Bodega Sur', 'Bodega de almacenamiento, en diseño.', null, ProjectStatus::Draft, $this->day(30), $this->day(300)));
        $this->bus->dispatch(new AssignMember($bodega, $pm, ProjectRole::ProjectManager));
    }

    /** Active: the first stage completed, the second under way, money in and out, a caja menor cycle signed off. */
    private function torreNorte(int $admin, int $pm, int $lead, int $lead2): void
    {
        $project = $this->id(new CreateProject('Torre Norte', 'Edificio de doce pisos.', null, ProjectStatus::Draft, $this->day(-120), $this->day(150)));
        $this->bus->dispatch(new AssignMember($project, $pm, ProjectRole::ProjectManager));
        $this->bus->dispatch(new AssignMember($project, $lead, ProjectRole::TeamLead));
        $this->bus->dispatch(new AssignMember($project, $lead2, ProjectRole::TeamLead));
        $c = $this->categories($project, ['Materiales', 'Nómina', 'Equipos']);

        $foundation = $this->stage($project, 'Cimentación', -120, -40, [
            [$c['Materiales'], 'Concreto 3000 PSI', 'm³', '120', '450000'],
            [$c['Nómina'], 'Cuadrilla de obra', 'mes', '3', '8000000'],
            [$c['Equipos'], 'Alquiler de retroexcavadora', 'día', '10', '600000'],
        ], [['Excavación', 3000, -100], ['Vaciado de zapatas', 7000, -45]]);
        $structure = $this->stage($project, 'Estructura', -39, 60, [
            [$c['Materiales'], 'Acero de refuerzo', 'kg', '12000', '5200'],
            [$c['Materiales'], 'Concreto 4000 PSI', 'm³', '200', '520000'],
            [$c['Nómina'], 'Cuadrilla de obra', 'mes', '4', '9000000'],
        ], [['Columnas piso 1 a 4', 3000, -10], ['Columnas piso 5 a 8', 3500, 25], ['Placa de cubierta', 3500, 58]]);
        $this->stage($project, 'Acabados', 61, 150, [
            [$c['Materiales'], 'Pintura y estuco', 'm²', '3500', '28000'],
            [$c['Nómina'], 'Cuadrilla de acabados', 'mes', '3', '7500000'],
        ], [['Fachada', 5000, 110], ['Entrega', 5000, 150]]);
        $this->bus->dispatch(new SetContingency($project, '15000000'));
        $this->bus->dispatch(new SubmitBudget($project, $pm));
        $this->bus->dispatch(new ApproveBudget($project, $admin));

        $this->deposit($project, $admin, -115, 'TRX-4411', [[LedgerAccount::Stage, '90000000', $foundation], [LedgerAccount::PettyCash, '3000000', null], [LedgerAccount::Contingency, '5000000', null]]);
        $this->bus->dispatch(new StartStage($foundation, $this->day(-118)));
        foreach ($this->plans->stage($foundation)->getMilestones() as $i => $milestone) {
            $this->bus->dispatch(new CompleteMilestone((int) $milestone->getId(), $pm, $this->day(0 === $i ? -98 : -42), 0 === $i ? 'Sin novedades.' : null));
        }

        // The first month: the PM pays from the stage and the caja menor, a Team Lead is paid back, the cycle closes.
        $this->spend($project, $pm, PaidFrom::Stage, $foundation, $c['Materiales'], -110, '52000000', 'Concreto 3000 PSI', 'Concretos del Norte', 'FV-1021');
        $this->spend($project, $pm, PaidFrom::PettyCash, $foundation, $c['Materiales'], -90, '380000', 'Clavos y alambre', 'Ferretería El Tornillo', null);
        $paidBack = $this->spend($project, $lead, PaidFrom::OutOfPocket, $foundation, $c['Materiales'], -80, '240000', 'Transporte de materiales', null, null, $pm);
        $this->bus->dispatch(new ReimburseExpenses($project, $pm, [$paidBack], $this->day(-75), PayoutMethod::Cash, null));
        $this->spend($project, $pm, PaidFrom::Stage, $foundation, $c['Nómina'], -60, '24000000', 'Nómina de la cuadrilla', null, null);
        $cycle = $this->id(new CloseCycle($project, $pm, 'Cierre del primer mes.'));
        $this->bus->dispatch(new SignOffCycle($cycle, $admin));

        // Then the second stage: more money, more spending, Team Lead expenses at every step of their approval.
        $this->deposit($project, $admin, -38, 'TRX-4602', [[LedgerAccount::Stage, '80000000', $structure], [LedgerAccount::PettyCash, '2000000', null]]);
        $this->bus->dispatch(new StartStage($structure, $this->day(-38)));
        $this->bus->dispatch(new CompleteMilestone((int) $this->plans->stage($structure)->getMilestones()[0]->getId(), $pm, $this->day(-8)));
        $this->spend($project, $pm, PaidFrom::Stage, $structure, $c['Materiales'], -30, '46800000', 'Acero de refuerzo', 'Aceros Andinos', 'FV-2207');
        $this->spend($project, $pm, PaidFrom::PettyCash, $structure, $c['Equipos'], -12, '650000', 'Repuestos de la mezcladora', null, null);
        $this->spend($project, $lead, PaidFrom::OutOfPocket, $structure, $c['Materiales'], -20, '185000', 'Amarres y separadores', 'Ferretería El Tornillo', 'T-332', $pm);
        $this->spend($project, $lead2, PaidFrom::OutOfPocket, $structure, $c['Equipos'], -6, '720000', 'Alquiler de vibrador', 'Equipos JM', 'FV-88', $pm);
        $this->spend($project, $lead2, PaidFrom::OutOfPocket, $structure, $c['Materiales'], -3, '95000', 'Cinta y señalización', null, null);
        $rejected = $this->spend($project, $lead, PaidFrom::OutOfPocket, $structure, $c['Nómina'], -2, '300000', 'Almuerzos de la cuadrilla', null, null);
        $this->bus->dispatch(new RejectExpense($rejected, $pm, false, 'Los almuerzos van por nómina, no como gasto.'));

        // The first stage is closed: its leftover moves to the second.
        $this->bus->dispatch(new CompleteStage($foundation, $this->day(-40), $admin));
    }

    /** Draft whose budget waits for the Admin. */
    private function casaCampestre(int $admin, int $pm, int $lead): void
    {
        $project = $this->id(new CreateProject('Casa Campestre', 'Vivienda de dos plantas en La Calera.', null, ProjectStatus::Draft, $this->day(20), $this->day(200)));
        $this->bus->dispatch(new AssignMember($project, $pm, ProjectRole::ProjectManager));
        $this->bus->dispatch(new AssignMember($project, $lead, ProjectRole::TeamLead));
        $c = $this->categories($project, ['Materiales', 'Mano de obra']);
        $this->stage($project, 'Obra negra', 20, 110, [
            [$c['Materiales'], 'Bloque y mortero', 'm²', '480', '65000'],
            [$c['Mano de obra'], 'Maestro y ayudantes', 'mes', '3', '6500000'],
        ], [['Muros primer piso', 5000, 60], ['Placa entrepiso', 5000, 105]]);
        $this->stage($project, 'Acabados', 111, 200, [[$c['Materiales'], 'Enchapes', 'm²', '220', '85000']], [['Entrega', 10000, 200]]);
        $this->bus->dispatch(new SetContingency($project, '4000000'));
        $this->bus->dispatch(new SubmitBudget($project, $pm));
    }

    /**
     * @param list<string> $names
     *
     * @return array<string, int>
     */
    private function categories(int $project, array $names): array
    {
        foreach ($names as $name) {
            $this->bus->dispatch(new AddCategory($project, $name));
        }
        $ids = [];
        foreach ($this->plans->categoriesOf($project) as $category) {
            $ids[$category->getName()] = (int) $category->getId();
        }

        return $ids;
    }

    /**
     * @param list<array{int, string, string, string, string}> $lines      category, description, unit, quantity, unit price
     * @param list<array{string, int, int}>                    $milestones name, weight (bp), planned day
     */
    private function stage(int $project, string $name, int $start, int $end, array $lines, array $milestones): int
    {
        $this->bus->dispatch(new AddStage($project, $name, $this->day($start), $this->day($end)));
        $stages = $this->plans->stagesOf($project);
        $stage = (int) $stages[\count($stages) - 1]->getId();
        foreach ($lines as [$category, $description, $unit, $quantity, $price]) {
            $this->bus->dispatch(new AddLine($stage, $category, $description, $unit, $quantity, $price));
        }
        foreach ($milestones as [$milestone, $weight, $day]) {
            $this->bus->dispatch(new AddMilestone($stage, $milestone, $weight, $this->day($day)));
        }

        return $stage;
    }

    /**
     * @param list<array{LedgerAccount, string, ?int}> $parts
     */
    private function deposit(int $project, int $admin, int $day, string $reference, array $parts): void
    {
        $this->bus->dispatch(new RecordDeposit($project, $admin, $this->day($day), PaymentMethod::Transfer, $reference, null, array_map(
            static fn (array $p): Allocation => new Allocation($p[0], $p[1], $p[2]),
            $parts,
        )));
    }

    /** An expense; a Team Lead's gets a receipt, and is approved when `$approvedBy` is given. */
    private function spend(int $project, int $by, PaidFrom $from, int $stage, int $category, int $day, string $amount, string $description, ?string $supplier, ?string $invoice, ?int $approvedBy = null): int
    {
        $manager = PaidFrom::OutOfPocket !== $from;
        $expense = $this->id(new RecordExpense($project, $by, $manager, new ExpenseDetails($stage, $category, $this->day($day), $amount, $description, $supplier, $invoice), $from));
        if (!$manager) {
            $receipt = (string) tempnam(sys_get_temp_dir(), 'seed');
            file_put_contents($receipt, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
            $this->bus->dispatch(new AddReceipt($expense, $by, false, $receipt, 'factura.pdf', (int) filesize($receipt)));
        }
        if (null !== $approvedBy) {
            $this->bus->dispatch(new ApproveExpense($expense, $approvedBy, false));
        }

        return $expense;
    }

    private function id(object $command): int
    {
        $id = $this->bus->dispatch($command);
        \assert($id instanceof NewId);

        return $id->value();
    }

    private function day(int $offset): \DateTimeImmutable
    {
        return $this->today->modify(\sprintf('%+d days', $offset));
    }
}
