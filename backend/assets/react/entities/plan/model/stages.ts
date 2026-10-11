import type {Plan, PlanStage} from '../api/planApi';

/**
 * The stages that exist outside the plan tab: a stage added to the approved budget takes expenses and money only
 * once the Admin approves its addition (the API refuses it before that).
 */
export function approvedStages(plan: Plan): PlanStage[] {
  return plan.stages.filter((s) => s.approved);
}
