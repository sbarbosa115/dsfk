<?php

namespace App\Security;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Project>
 */
class ProjectVoter extends Voter
{
    public const VIEW = 'PROJECT_VIEW';
    /** Stages, budget drafts, milestones. */
    public const PLAN = 'PROJECT_PLAN';
    /** Budget amounts and financial figures (Admin and PM, not Team Leads). */
    public const VIEW_FINANCIALS = 'PROJECT_VIEW_FINANCIALS';
    /** Project settings, members, approvals. */
    public const ADMINISTER = 'PROJECT_ADMINISTER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Project && \in_array($attribute, [self::VIEW, self::VIEW_FINANCIALS, self::PLAN, self::ADMINISTER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        $member = $subject->findMember($user);

        return match ($attribute) {
            self::VIEW => null !== $member,
            self::PLAN, self::VIEW_FINANCIALS => ProjectRole::ProjectManager === $member?->getRole(),
            default => false,
        };
    }
}
