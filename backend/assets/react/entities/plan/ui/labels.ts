import {t} from '@/shared/i18n';
import type {BudgetStatus, PlanMilestone, StageStatus} from '../api/planApi';

export function budgetTone(status: BudgetStatus): string {
  return `budget_${status.toLowerCase()}`;
}

export function stageTone(status: StageStatus): string {
  return `stage_${status.toLowerCase()}`;
}

const STAGE_STATUSES: ReadonlyArray<StageStatus> = [
  'PENDING',
  'IN_PROGRESS',
  'COMPLETED',
];

/** The key to a table tinted by its stages' status (QA-0009). */
export function stageLegend(): Array<{value: string; label: string}> {
  return STAGE_STATUSES.map((s) => ({
    value: stageTone(s),
    label: t(`plan.stageStatus.${s}`),
  }));
}

/** Met, late, or neither (no tone). */
export function milestoneTone(milestone: PlanMilestone): string | null {
  if (milestone.completedAt) {
    return 'milestone_done';
  }

  return milestone.overdue ? 'milestone_overdue' : null;
}

export function milestoneLabel(milestone: PlanMilestone): string | null {
  const tone = milestoneTone(milestone);

  return tone === null ? null : t(`plan.milestoneState.${tone}`);
}
