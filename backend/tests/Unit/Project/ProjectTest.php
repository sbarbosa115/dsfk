<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectRole;
use App\Project\Domain\Model\ProjectStatus;
use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\DomainError;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotFound;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function testANewProjectIsADraftInItsCurrency(): void
    {
        $project = $this->project(currency: 'usd');

        self::assertSame(ProjectStatus::Draft, $project->getStatus());
        self::assertSame('USD', $project->getCurrency());
        self::assertSame('Torre Norte', $project->getName());
    }

    public function testThePlannedEndCannotBeBeforeTheStart(): void
    {
        try {
            $this->project()->schedule(new \DateTimeImmutable('2027-01-01'), new \DateTimeImmutable('2026-01-01'));
            self::fail('an end before the start must be refused');
        } catch (InvalidValue $e) {
            self::assertSame(['plannedEnd' => ['The end date cannot be before the start date.']], $e->extra['violations']);
        }
    }

    public function testAProjectHasAtMostOneProjectManagerBecauseThePmHoldsTheCajaMenor(): void
    {
        $project = $this->project();
        $project->assign(1, ProjectRole::ProjectManager);

        $this->expectExceptionObject(new Conflict('project_manager_exists'));

        $project->assign(2, ProjectRole::ProjectManager);
    }

    public function testThePmCanStepDownSoAnotherCanTakeOver(): void
    {
        $project = $this->project();
        $project->assign(1, ProjectRole::ProjectManager);

        $project->assign(1, ProjectRole::TeamLead);
        $project->assign(2, ProjectRole::ProjectManager);

        self::assertSame(ProjectRole::TeamLead, $project->roleOf(1));
        self::assertSame(ProjectRole::ProjectManager, $project->roleOf(2));
        self::assertCount(2, $project->getMembers());
    }

    public function testAssigningSomeoneAlreadyInTheProjectChangesTheirRoleInsteadOfAddingThemTwice(): void
    {
        $project = $this->project();
        $project->assign(5, ProjectRole::TeamLead);
        $project->assign(5, ProjectRole::TeamLead);

        self::assertCount(1, $project->getMembers());
    }

    public function testRemovingAnUnknownMemberIsNotFound(): void
    {
        $this->expectExceptionObject(new NotFound('member_not_found'));

        $this->project()->removeMember(99);
    }

    public function testNonMembersHaveNoRole(): void
    {
        self::assertNull($this->project()->roleOf(7));
    }

    public function testTheNameIsRequired(): void
    {
        $this->expectException(DomainError::class);

        $this->project()->rename('   ');
    }

    private function project(string $currency = 'COP'): Project
    {
        return new Project(' Torre Norte ', $currency, new \DateTimeImmutable('2026-09-01'));
    }
}
