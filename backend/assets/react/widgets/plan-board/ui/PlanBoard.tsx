import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {
  fetchPlan,
  lateMilestones,
  planKey,
  stagesOpenByDefault,
  type PlanStage,
  type StageStatus,
} from '@/entities/plan';
import {CategoriesPanel} from '@/features/plan-categories';
import {StageFormModal} from '@/features/plan-stages';
import {t} from '@/shared/i18n';
import {
  ActionButton,
  EmptyState,
  ErrorState,
  Loading,
  TabIntro,
  Tabs,
} from '@/shared/ui';
import {BudgetCard} from './BudgetCard';
import {StageCard} from './StageCard';

type Filter = 'ALL' | StageStatus;

const FILTERS: ReadonlyArray<Filter> = [
  'ALL',
  'IN_PROGRESS',
  'PENDING',
  'COMPLETED',
];

/** The "Presupuesto y plan" tab of a project. */
export function PlanBoard({projectId}: {projectId: number}) {
  const plan = useQuery({
    queryKey: planKey(projectId),
    queryFn: () => fetchPlan(projectId),
  });
  const [adding, setAdding] = useState(false);
  const [filter, setFilter] = useState<Filter>('ALL');
  // Only what the user opened or closed; the rest follows stagesOpenByDefault as the plan changes.
  const [toggled, setToggled] = useState<Record<number, boolean>>({});

  if (plan.error) {
    return (
      <ErrorState error={plan.error} onRetry={() => void plan.refetch()} />
    );
  }
  if (!plan.data) {
    return <Loading />;
  }
  const p = plan.data;
  const addStage = p.permissions.addStage ? (
    <ActionButton
      action="setup"
      main
      size="md"
      icon="plus"
      onClick={() => setAdding(true)}
    >
      {t('plan.addStage')}
    </ActionButton>
  ) : null;
  const byDefault = stagesOpenByDefault(p.stages);
  const isOpen = (stage: PlanStage) =>
    toggled[stage.id] ?? byDefault.has(stage.id);
  const shown = p.stages
    .map((stage, index) => ({stage, index}))
    .filter(({stage}) => filter === 'ALL' || stage.status === filter);

  return (
    <div className="settings-sections">
      <TabIntro>
        {p.permissions.viewFinancials
          ? t('plan.intro')
          : t('plan.introTeamLead')}
      </TabIntro>
      <BudgetCard plan={p} />
      {(p.permissions.viewFinancials || p.categories.length > 0) && (
        <CategoriesPanel plan={p} />
      )}
      {/* "Agregar etapa" ends the stage list's own header, once, even when the list is empty (QA-0005). */}
      <div className="section-title stage-list-header">
        <div>
          <h3>{t('plan.stages')}</h3>
          {p.stages.length > 0 && <StageCounts stages={p.stages} />}
        </div>
        <div className="stage-list-tools">
          {p.stages.length > 1 && (
            <Tabs<Filter>
              value={filter}
              label={t('plan.stageFilter.label')}
              options={FILTERS.map((value) => ({
                value,
                label: t(`plan.stageFilter.${value}`),
              }))}
              onChange={setFilter}
            />
          )}
          {addStage}
        </div>
      </div>
      {p.stages.length === 0 ? (
        <EmptyState>{t('plan.noStages')}</EmptyState>
      ) : shown.length === 0 ? (
        <EmptyState>{t('plan.stageFilter.empty')}</EmptyState>
      ) : (
        shown.map(({stage, index}) => (
          <StageCard
            key={stage.id}
            plan={p}
            stage={stage}
            index={index}
            open={isOpen(stage)}
            onToggle={() =>
              setToggled((current) => ({
                ...current,
                [stage.id]: !isOpen(stage),
              }))
            }
          />
        ))
      )}
      {adding && <StageFormModal plan={p} onClose={() => setAdding(false)} />}
    </div>
  );
}

/** "2 etapas · 1 en curso · 1 finalizada · 3 hitos atrasados": the stage list at a glance. */
function StageCounts({stages}: {stages: ReadonlyArray<PlanStage>}) {
  const running = stages.filter((s) => s.status === 'IN_PROGRESS').length;
  const done = stages.filter((s) => s.status === 'COMPLETED').length;
  const late = stages.reduce((n, s) => n + lateMilestones(s).length, 0);

  return (
    <p className="small muted stage-counts">
      {t('plan.stageSummary.stages', {count: stages.length})}
      {running > 0 &&
        ` · ${t('plan.stageSummary.inProgress', {count: running})}`}
      {done > 0 && ` · ${t('plan.stageSummary.completed', {count: done})}`}
      {late > 0 && (
        <>
          {' · '}
          <span className="text-danger strong">
            {t('plan.stageSummary.late', {count: late})}
          </span>
        </>
      )}
    </p>
  );
}
