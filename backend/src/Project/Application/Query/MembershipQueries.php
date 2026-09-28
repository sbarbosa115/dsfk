<?php

declare(strict_types=1);

namespace App\Project\Application\Query;

/** Memberships with the project's name, for the Identity context (the signed-in user, the users list). */
interface MembershipQueries
{
    /**
     * @return list<array{projectId: int, projectName: string, role: string}>
     */
    public function ofUser(int $userId): array;

    /**
     * @return array<int, list<array{projectId: int, projectName: string, role: string}>> by user id
     */
    public function ofAllUsers(): array;
}
