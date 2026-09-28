<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

/** What a user may do in one project. The Project context's voter answers these; the subject is a project id. */
final class ProjectPermission
{
    /** Any member (and Admins). */
    public const VIEW = 'PROJECT_VIEW';
    /** Stages, budget drafts, milestones, expenses from project money: the Project Manager (and Admins). */
    public const PLAN = 'PROJECT_PLAN';
    /** Money figures: the Project Manager (and Admins), never Team Leads. */
    public const VIEW_FINANCIALS = 'PROJECT_VIEW_FINANCIALS';
    /** Members, approvals, deposits, voids: Admins only. */
    public const ADMINISTER = 'PROJECT_ADMINISTER';
}
