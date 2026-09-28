import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {fetchPlan, planKey} from '@/entities/plan';
import {CategoriesPanel} from '@/features/plan-categories';
import {StageFormModal} from '@/features/plan-stages';
import {t} from '@/shared/i18n';
import {Button, EmptyState, ErrorState, Loading, TabIntro} from '@/shared/ui';
import {BudgetCard} from './BudgetCard';
import {StageCard} from './StageCard';

/** The "Presupuesto y plan" tab of a project. */
export function PlanBoard({projectId}: {projectId: number}) {
  const plan = useQuery({
    queryKey: planKey(projectId),
    queryFn: () => fetchPlan(projectId),
  });
  const [adding, setAdding] = useState(false);

  if (plan.error) {
    return (
      <ErrorState error={plan.error} onRetry={() => void plan.refetch()} />
    );
  }
  if (!plan.data) {
    return <Loading />;
  }
  const p = plan.data;
  const addStage = p.permissions.edit ? (
    <Button onClick={() => setAdding(true)}>{t('plan.addStage')}</Button>
  ) : null;

  return (
    <div className="settings-sections">
      <TabIntro action={addStage}>
        {p.permissions.viewFinancials
          ? t('plan.intro')
          : t('plan.introTeamLead')}
      </TabIntro>
      <BudgetCard plan={p} />
      {(p.permissions.viewFinancials || p.categories.length > 0) && (
        <CategoriesPanel plan={p} />
      )}
      {p.stages.length === 0 ? (
        <EmptyState action={addStage}>{t('plan.noStages')}</EmptyState>
      ) : (
        p.stages.map((stage, index) => (
          <StageCard key={stage.id} plan={p} stage={stage} index={index} />
        ))
      )}
      {adding && <StageFormModal plan={p} onClose={() => setAdding(false)} />}
    </div>
  );
}
