<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Identity\Domain\Model\User;
use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectRole;
use App\Tests\Functional\ApiTestCase;
use App\Tests\Functional\BuildsPlan;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Mime\Email;

/** Emails the rules send: who gets them, about what. They are queued on Messenger, sent by the consumer. */
final class NotificationTest extends ApiTestCase
{
    use BuildsPlan;

    private User $lead;
    private Project $project;
    private int $foundation;
    /** @var array<string, int> */
    private array $categories;

    protected function setUp(): void
    {
        parent::setUp();
        // One kernel for the whole test, so the queued emails of every request stay in the same transport.
        $this->client->disableReboot();
        Clock::set(new MockClock('2026-10-25 10:00:00', 'America/Bogota'));
        $this->admin = $this->createUser('admin@example.com', admin: true);
        $this->createUser('former-admin@example.com', admin: true, active: false);
        $this->pm = $this->createUser('pm@example.com');
        $this->lead = $this->createUser('lead@example.com');
        $this->project = $this->createProject('Torre', [[$this->pm, ProjectRole::ProjectManager], [$this->lead, ProjectRole::TeamLead]]);
        $this->base = '/api/projects/'.$this->project->getId();
        $this->loginAs($this->pm);
        $plan = $this->buildPlan();
        $this->foundation = $plan['stages'][0]['id'];
        $this->categories = array_column($plan['categories'], 'id', 'name');
    }

    public function testTheBudgetWorkflowTellsTheAdminsAndThenThePm(): void
    {
        // Switching users starts a new container: the emails are read after each step.
        $this->loginAs($this->pm);
        $this->request('POST', $this->base.'/budget/submit');
        $submitted = $this->mails();
        self::assertSame([['admin@example.com', 'Presupuesto enviado a aprobación: Torre']], array_column($submitted, 'head'), 'active admins only');
        self::assertStringContainsString('Pm envió el presupuesto del proyecto Torre para tu aprobación.', $submitted[0]['html']);

        $this->loginAs($this->admin);
        $this->request('POST', $this->base.'/budget/approve');
        self::assertSame([['pm@example.com', 'Presupuesto aprobado: Torre']], array_column($this->mails(), 'head'));
    }

    public function testATeamLeadsExpenseGoesToThePmAndARejectionBackToTheLead(): void
    {
        $this->approve();
        $this->mails();
        $this->loginAs($this->lead);
        $expense = $this->json('POST', $this->base.'/expenses', $this->expense('120000'));
        self::assertSame([['pm@example.com', 'Gasto por aprobar: Cemento']], array_column($this->mails(), 'head'));

        $this->loginAs($this->pm);
        $this->json('POST', "/api/expenses/{$expense['id']}/reject", ['reason' => 'Falta la factura']);
        $mails = $this->mails();
        self::assertSame(['lead@example.com', 'Gasto rechazado: Cemento'], $mails[0]['head']);
        self::assertStringContainsString('Falta la factura', $mails[0]['html']);
        self::assertStringContainsString('$ 120.000', $mails[0]['html']);
    }

    public function testPeoplesTextStaysOnOneSubjectLineAndIsEscapedInTheBody(): void
    {
        $this->approve();
        $this->mails();
        $this->loginAs($this->lead);
        $this->json('POST', $this->base.'/expenses', ['description' => "Cemento\r\nBcc: spy@example.com <b>x</b>"] + $this->expense('1000'));

        $mail = $this->mails()[0];
        self::assertSame(['pm@example.com', 'Gasto por aprobar: Cemento Bcc: spy@example.com <b>x</b>'], $mail['head']);
        self::assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $mail['html']);
    }

    public function testCrossingABudgetWarningTellsTheAdminsAndThePmOnce(): void
    {
        $this->approve();
        $this->loginAs($this->admin);
        $this->json('POST', $this->base.'/deposits', ['date' => '2026-10-20', 'method' => 'CASH', 'allocations' => [['destination' => 'STAGE', 'stageId' => $this->foundation, 'amount' => '1437506.25']]]);
        $this->mails();
        $this->loginAs($this->pm);

        // Cimentación's budget is 1,437,506.25: 80 % is 1,150,005.
        $this->json('POST', $this->base.'/expenses', $this->expense('1000000', 'STAGE'));
        self::assertSame([], $this->mails());
        $this->json('POST', $this->base.'/expenses', $this->expense('200000', 'STAGE'));

        $heads = array_column($this->mails(), 'head');
        self::assertContains(['admin@example.com', 'Alerta de presupuesto (80%): Torre'], $heads);
        self::assertContains(['pm@example.com', 'Alerta de presupuesto (80%): Torre'], $heads);
    }

    public function testALowCajaMenorWarnsWhenItDropsBelowItsLevel(): void
    {
        $this->approve();
        $this->loginAs($this->admin);
        $this->json('POST', $this->base.'/deposits', ['date' => '2026-10-20', 'method' => 'CASH', 'allocations' => [['destination' => 'PETTY_CASH', 'amount' => '100000']]]);
        $this->mails();
        $this->loginAs($this->pm);

        // The default level is 20 % of the last top-up: 20,000.
        $this->json('POST', $this->base.'/expenses', $this->expense('70000', 'PETTY_CASH'));
        self::assertSame([], $this->mails());
        $this->json('POST', $this->base.'/expenses', $this->expense('15000', 'PETTY_CASH'));

        $mails = $this->mails();
        self::assertContains(['pm@example.com', 'Caja menor baja: Torre'], array_column($mails, 'head'));
        self::assertStringContainsString('$ 15.000', $mails[0]['html']);
    }

    public function testTheDailyDigestListsWhatIsPendingPerActiveProject(): void
    {
        $this->approve();
        $this->loginAs($this->lead);
        $this->json('POST', $this->base.'/expenses', $this->expense('1000'));
        $this->mails();

        $command = new CommandTester((new Application(self::$kernel ?? self::bootKernel()))->find('app:alerts:daily'));
        $command->execute([]);

        $command->assertCommandIsSuccessful();
        $mails = $this->mails();
        self::assertSame(['admin@example.com', 'Resumen diario: Torre'], $mails[0]['head']);
        self::assertStringContainsString('Cimentación · Excavación (planeado para el 20/10/2026)', $mails[0]['html'], 'due Oct 20, not met');
        self::assertStringContainsString('Gastos por aprobar: 1', $mails[0]['html']);
    }

    /**
     * The emails queued since the last call.
     *
     * @phpstan-impure
     *
     * @return list<array{head: array{string, string}, html: string}>
     */
    private function mails(): array
    {
        $transport = self::getContainer()->get('messenger.transport.async');
        \assert($transport instanceof InMemoryTransport);
        $mails = [];
        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof SendEmailMessage && ($email = $message->getMessage()) instanceof Email) {
                if ($email instanceof TemplatedEmail && null === $email->getHtmlBody()) {
                    // The consumer renders queued templated emails when it sends them.
                    self::getContainer()->get('twig.mime_body_renderer')->render($email);
                }
                $mails[] = ['head' => [$email->getTo()[0]->getAddress(), (string) $email->getSubject()], 'html' => (string) $email->getHtmlBody()];
            }
        }
        $transport->reset();

        return $mails;
    }

    /**
     * @return array<string, mixed>
     */
    private function expense(string $amount, ?string $paidFrom = null): array
    {
        return ['stageId' => $this->foundation, 'categoryId' => $this->categories['Materiales'], 'date' => '2026-10-25', 'amount' => $amount, 'description' => 'Cemento', 'paidFrom' => $paidFrom];
    }
}
