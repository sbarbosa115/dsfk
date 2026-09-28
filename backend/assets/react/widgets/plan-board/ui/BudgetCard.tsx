import {budgetTone, type Plan} from '@/entities/plan';
import {BudgetActions, ContingencyButton} from '@/features/plan-review';
import {t} from '@/shared/i18n';
import {formatDate, formatMoney, formatPercent} from '@/shared/lib/format';
import {Alert, Badge, ProgressBar, Stat} from '@/shared/ui';

/** The budget at a glance: its status, figures, the project's progress, what blocks it and its workflow. */
export function BudgetCard({plan}: {plan: Plan}) {
  const {budget} = plan;
  const currency = plan.project.currency;
  const lastReturn = [...(budget?.events ?? [])]
    .reverse()
    .find((e) => e.status === 'RETURNED');
  const stage = (id?: number | null) => plan.stages.find((s) => s.id === id);

  return (
    <section className="card">
      <div className="card-header">
        <div className="stage-title">
          <h2>{t('plan.budget')}</h2>
          <Badge value={budgetTone(plan.budgetStatus)}>
            {t(`plan.budgetStatus.${plan.budgetStatus}`)}
          </Badge>
        </div>
        <div className="row-actions">
          <BudgetActions plan={plan} />
        </div>
      </div>
      {budget ? (
        <div className="stat-grid">
          <Stat
            label={t('plan.stagesTotal')}
            value={formatMoney(budget.stagesTotal, currency)}
            money
          />
          <Stat
            label={t('plan.contingency')}
            value={formatMoney(budget.contingency, currency)}
            money
          >
            <ContingencyButton plan={plan} />
          </Stat>
          <Stat
            label={t('plan.total')}
            value={formatMoney(budget.total, currency)}
            money
          />
          <Stat
            label={t('plan.progress')}
            value={formatPercent(plan.progress)}
            money
          >
            <ProgressBar value={plan.progress} label={t('plan.progress')} />
          </Stat>
        </div>
      ) : (
        <ProgressBar value={plan.progress} label={t('plan.progress')} />
      )}
      {plan.budgetStatus === 'APPROVED' && budget?.approvedAt && (
        <Alert kind="success">
          {t('plan.lockedNote', {date: formatDate(budget.approvedAt)})}
        </Alert>
      )}
      {plan.budgetStatus === 'SUBMITTED' && (
        <Alert kind="info">{t('plan.submittedNote')}</Alert>
      )}
      {plan.budgetStatus === 'RETURNED' && lastReturn && (
        <Alert kind="warning">
          {t('plan.returnedNote', {
            user: lastReturn.user.fullName,
            comment: lastReturn.comment,
          })}
        </Alert>
      )}
      {plan.permissions.submit && (plan.issues?.length ?? 0) > 0 && (
        <Alert kind="info">
          <strong>{t('plan.issuesTitle')}</strong>
          <ul className="issue-list">
            {plan.issues?.map((issue, i) => (
              <li key={i}>
                {t(`plan.issues.${issue.code}`, {
                  stage: stage(issue.stageId)?.name ?? '',
                  total: formatPercent(
                    stage(issue.stageId)?.milestoneWeightTotal ?? 0,
                  ),
                })}
              </li>
            ))}
          </ul>
        </Alert>
      )}
    </section>
  );
}
