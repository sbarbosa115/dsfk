<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use App\Shared\Domain\Error\NotAllowed;
use App\Shared\Domain\Error\NotFound;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The access check every project-scoped endpoint starts with. Someone outside the project gets 404, so they
 * cannot learn it exists; a member whose role does not allow the action gets 403.
 */
final readonly class ProjectGuard
{
    public function __construct(private AuthorizationCheckerInterface $auth)
    {
    }

    /**
     * @param ProjectPermission::* $permission
     *
     * @throws NotFound   project_not_found
     * @throws NotAllowed forbidden
     */
    public function require(string $permission, int $projectId): void
    {
        if (!$this->auth->isGranted(ProjectPermission::VIEW, $projectId)) {
            throw new NotFound('project_not_found');
        }
        if (ProjectPermission::VIEW !== $permission && !$this->auth->isGranted($permission, $projectId)) {
            throw new NotAllowed('forbidden');
        }
    }

    public function allows(string $permission, int $projectId): bool
    {
        return $this->auth->isGranted($permission, $projectId);
    }
}
