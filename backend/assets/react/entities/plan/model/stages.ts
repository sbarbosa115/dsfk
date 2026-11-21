import type {Plan, PlanMilestone, PlanStage} from '../api/planApi';

/**
 * The stages that exist outside the plan tab: a stage added to the approved budget takes expenses and money only
 * once the Admin approves its addition (the API refuses it before that).
 */
export function approvedStages(plan: Plan): PlanStage[] {
  return plan.stages.filter((s) => s.approved);
}

/** Whole days from one YYYY-MM-DD date to another (negative when `to` comes first). */
export function daysBetween(from: string, to: string): number {
  const day = (iso: string) => {
    const [y, m, d] = iso.slice(0, 10).split('-').map(Number);
    return Date.UTC(y ?? 1970, (m ?? 1) - 1, d ?? 1) / 86_400_000;
  };

  return Math.round(day(to) - day(from));
}

/**
 * How much of a running stage's planned time has gone by, in basis points, to read its progress against: null when
 * the stage is not running or has no planned dates.
 */
export function stageTimeShare(stage: PlanStage, today: string): number | null {
  if (
    stage.status !== 'IN_PROGRESS' ||
    !stage.plannedStart ||
    !stage.plannedEnd
  ) {
    return null;
  }
  const span = daysBetween(stage.plannedStart, stage.plannedEnd);
  if (span <= 0) {
    return 10000;
  }
  const gone = daysBetween(stage.plannedStart, today);

  return Math.max(0, Math.min(10000, Math.round((gone / span) * 10000)));
}

/** Milestones whose planned date went by without being met. */
export function lateMilestones(stage: PlanStage): PlanMilestone[] {
  return stage.milestones.filter((m) => m.overdue && !m.completedAt);
}

/** The next milestone to meet: the first one neither met nor late, while the stage can still be worked on. */
export function nextMilestone(stage: PlanStage): PlanMilestone | null {
  if (!stage.approved || stage.status === 'COMPLETED') {
    return null;
  }

  return stage.milestones.find((m) => !m.completedAt && !m.overdue) ?? null;
}

/**
 * The stages shown open when the plan loads: the ones being written, running or late; if none, the first one not
 * finished, so the plan never opens with nothing to act on. The rest show only their summary.
 */
export function stagesOpenByDefault(
  stages: ReadonlyArray<PlanStage>,
): Set<number> {
  const open = stages.filter(
    (s) =>
      s.editable || s.status === 'IN_PROGRESS' || lateMilestones(s).length > 0,
  );
  const first = stages.find((s) => s.status !== 'COMPLETED');

  return new Set(
    (open.length > 0 ? open : first ? [first] : []).map((s) => s.id),
  );
}
