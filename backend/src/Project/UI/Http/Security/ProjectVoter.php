<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Security;

use App\Project\Application\Query\ProjectAccess;
use App\Project\Domain\Model\ProjectRole;
use App\Shared\Application\Security\Actor;
use App\Shared\UI\Http\ProjectPermission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Answers ProjectPermission::* for a project id: Admins pass every check; a Project Manager may plan and see
 * money; a Team Lead only views.
 *
 * @extends Voter<string, int>
 */
final class ProjectVoter extends Voter
{
    private const ATTRIBUTES = [ProjectPermission::VIEW, ProjectPermission::PLAN, ProjectPermission::VIEW_FINANCIALS, ProjectPermission::ADMINISTER];

    public function __construct(private readonly ProjectAccess $access)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \is_int($subject) && \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $actor = $token->getUser();
        if (!$actor instanceof Actor) {
            return false;
        }
        $role = $this->access->roleOf($subject, $actor->getId(), $actor->isAdmin());

        return match ($role) {
            null => false,
            ProjectAccess::ADMIN => true,
            ProjectRole::ProjectManager->value => ProjectPermission::ADMINISTER !== $attribute,
            default => ProjectPermission::VIEW === $attribute,
        };
    }
}
