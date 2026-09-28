<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Presenter;

use App\Project\Application\Port\UserDirectory;
use App\Project\Domain\Model\Project;
use App\Project\UI\Http\Output\MemberOutput;
use App\Project\UI\Http\Output\MemberUserOutput;
use App\Project\UI\Http\Output\ProjectOutput;
use App\Shared\Application\Security\Actor;

final readonly class ProjectPresenter
{
    public function __construct(private UserDirectory $users)
    {
    }

    /**
     * @param list<Project> $projects
     *
     * @return list<ProjectOutput>
     */
    public function presentMany(array $projects, Actor $actor): array
    {
        $userIds = [];
        foreach ($projects as $project) {
            foreach ($project->getMembers() as $member) {
                $userIds[] = $member->getUserId();
            }
        }
        // One query for every member's name, whatever the number of projects.
        $users = $this->users->findMany($userIds);

        $outputs = [];
        foreach ($projects as $project) {
            $members = [];
            foreach ($project->getMembers() as $member) {
                $user = $users[$member->getUserId()] ?? null;
                if (null !== $user) {
                    $members[] = new MemberOutput((int) $member->getId(), $member->getRole(), new MemberUserOutput($user->id, $user->email, $user->fullName));
                }
            }
            $outputs[] = new ProjectOutput(
                (int) $project->getId(),
                $project->getName(),
                $project->getDescription(),
                $project->getCurrency(),
                $project->getStatus(),
                $project->getPlannedStart()?->format('Y-m-d'),
                $project->getPlannedEnd()?->format('Y-m-d'),
                $project->getCreatedAt(),
                $actor->isAdmin() ? 'ADMIN' : ($project->roleOf($actor->getId())->value ?? 'TEAM_LEAD'),
                $members,
            );
        }

        return $outputs;
    }

    public function present(Project $project, Actor $actor): ProjectOutput
    {
        return $this->presentMany([$project], $actor)[0];
    }
}
