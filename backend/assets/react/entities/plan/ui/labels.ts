import {t} from '@/shared/i18n';
import type {BudgetStatus, PlanMilestone, StageStatus} from '../api/planApi';

export function budgetTone(status: BudgetStatus): string {
  return `budget_${status.toLowerCase()}`;
}

export function stageTone(status: StageStatus): string {
  return `stage_${status.toLowerCase()}`;
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
