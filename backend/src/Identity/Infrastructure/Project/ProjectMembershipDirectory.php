<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Project;

use App\Identity\Application\Port\MembershipDirectory;
use App\Identity\Application\Query\Membership;
use App\Project\Application\Query\MembershipQueries;

final readonly class ProjectMembershipDirectory implements MembershipDirectory
{
    public function __construct(private MembershipQueries $memberships)
    {
    }

    public function ofUser(int $userId): array
    {
        return self::map($this->memberships->ofUser($userId));
    }

    public function ofAllUsers(): array
    {
        return array_map(self::map(...), $this->memberships->ofAllUsers());
    }

    /**
     * @param list<array{projectId: int, projectName: string, role: string}> $rows
     *
     * @return list<Membership>
     */
    private static function map(array $rows): array
    {
        return array_map(static fn (array $r): Membership => new Membership($r['projectId'], $r['projectName'], $r['role']), $rows);
    }
}
