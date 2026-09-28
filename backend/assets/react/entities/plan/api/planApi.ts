import type {QueryClient} from '@tanstack/react-query';
import {api, type Schema} from '@/shared/api';

export type Plan = Schema<'PlanOutput'>;
export type PlanStage = Plan['stages'][number];
export type PlanLine = NonNullable<PlanStage['lines']>[number];
export type PlanMilestone = PlanStage['milestones'][number];
export type BudgetStatus = Plan['budgetStatus'];
export type StageStatus = PlanStage['status'];

export function planKey(projectId: number | string): readonly unknown[] {
  return ['plan', String(projectId)];
}

export function fetchPlan(projectId: number | string): Promise<Plan> {
  return api<Plan>(`/projects/${projectId}/plan`);
}

/**
 * Every plan write answers with the whole refreshed plan: `path` is under /api, e.g. `/stages/4/lines`.
 */
export function changePlan(
  path: string,
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE',
  body?: unknown,
): Promise<Plan> {
  return api<Plan>(path, {method, body});
}

/** Puts a plan the API answered with in the cache, so every part of the screen shows it at once. */
export function storePlan(queryClient: QueryClient, plan: Plan): void {
  queryClient.setQueryData(planKey(plan.project.id), plan);
  // Approving activates the project: its header shows the status.
  void queryClient.invalidateQueries({
    queryKey: ['project', String(plan.project.id)],
  });
}
