<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

/** What the signed-in user may do with this plan right now (the API refuses the rest anyway). */
final readonly class PlanPermissionsOutput
{
    public function __construct(
        /** Amounts are shown (PM and Admin). */
        public bool $viewFinancials,
        /** The budget's own stages, lines, milestones, order and contingency can be edited (it is a draft or was returned). */
        public bool $edit,
        /** Categories can be added and renamed (also after approval). */
        public bool $manageCategories,
        /** The budget, or its open amendment, can be sent to the Admin. */
        public bool $submit,
        /** The Admin may approve or return the budget, or its amendment (it is submitted). */
        public bool $review,
        /** Stages can be started and milestones met (the budget is approved). */
        public bool $track,
        public bool $reopenMilestones,
        /** "Agregar etapa": while the budget is written, and after approval into an amendment. */
        public bool $addStage,
    ) {
    }
}
