import {budgetTone, type BudgetStatus, type Plan} from '@/entities/plan';
import {BudgetActions, ContingencyButton} from '@/features/plan-review';
import {t} from '@/shared/i18n';
import {formatDate, formatMoney, formatPercent} from '@/shared/lib/format';
import {
  Alert,
  Badge,
  DataTable,
  ProgressBar,
  Row,
  RowLegend,
  Stat,
} from '@/shared/ui';

const AMENDMENT_STATUSES: ReadonlyArray<BudgetStatus> = [
  'DRAFT',
  'SUBMITTED',
  'RETURNED',
  'APPROVED',
];

/**
 * The budget at a glance: its status, figures, the project's progress, what blocks it and its workflow. Once
 * approved, its additions (Adiciones) are listed under it, and the open one carries the workflow.
 */
export function BudgetCard({plan}: {plan: Plan}) {
  const {budget} = plan;
  const currency = plan.project.currency;
  const open = budget?.amendments.find((a) => a.status !== 'APPROVED');
  // What the Admin said last about what is being written now: the budget, or its open addition.
  const lastReturn = [...(budget?.events ?? [])]
    .reverse()
    .find(
      (e) =>
        e.status === 'RETURNED' &&
        (e.amendment ?? null) === (open?.number ?? null),
    );
  const returned =
    open?.status === 'RETURNED' || plan.budgetStatus === 'RETURNED';
  const stage = (id?: number | null) => plan.stages.find((s) => s.id === id);

  return (
    <section className="card">
      <div className="card-header">
        <div className="stage-title">
          <h2>{t('plan.budget')}</h2>
          <Badge value={budgetTone(plan.budgetStatus)}>
            {t(`plan.budgetStatus.${plan.budgetStatus}`)}
          </Badge>
          {open && (
            <Badge value={budgetTone(open.status)}>
              {t('plan.amendment.badge', {number: open.number})}
              {' · '}
              {t(`plan.amendment.status.${open.status}`)}
            </Badge>
          )}
        </div>
        <div className="row-actions">
          <BudgetActions plan={plan} />
        </div>
      </div>
      {/* What the Admin asked to change comes before the figures: it is what to read first (QA-0010). */}
      {returned && lastReturn && (
        <Alert kind="warning">
          {t('plan.returnedNote', {
            user: lastReturn.user.fullName,
            comment: lastReturn.comment,
          })}
        </Alert>
      )}
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
            <ProgressBar
              value={plan.progress}
              label={t('plan.progress')}
              showValue={false}
            />
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
      {open?.status === 'SUBMITTED' && (
        <Alert kind="info">
          {t('plan.amendment.submittedNote', {number: open.number})}
        </Alert>
      )}
      {plan.permissions.submit && (plan.issues?.length ?? 0) > 0 && (
        <Alert kind="info">
          <strong>
            {open ? t('plan.amendment.issuesTitle') : t('plan.issuesTitle')}
          </strong>
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
      {budget && budget.amendments.length > 0 && (
        <>
          <div className="section-title">
            <h3>{t('plan.amendment.list')}</h3>
          </div>
          <RowLegend
            // Only the colours the list shows (QA-0009).
            statuses={AMENDMENT_STATUSES.filter((s) =>
              budget.amendments.some((a) => a.status === s),
            ).map((s) => ({
              value: budgetTone(s),
              label: t(`plan.amendment.status.${s}`),
            }))}
          />
          <DataTable
            label={t('plan.amendment.list')}
            actions={false}
            columns={[
              t('plan.amendment.number'),
              t('plan.amendment.stages'),
              t('plan.amendment.approvedAt'),
              t('plan.amendment.total'),
            ]}
            rows={budget.amendments}
            renderRow={(a) => (
              <Row
                key={a.number}
                status={budgetTone(a.status)}
                label={t(`plan.amendment.status.${a.status}`)}
              >
                <td className="strong nowrap">
                  {t('plan.amendment.badge', {number: a.number})}
                </td>
                <td>{a.stages.join(', ')}</td>
                <td className="nowrap">
                  {a.approvedAt ? formatDate(a.approvedAt) : '—'}
                </td>
                <td className="num">{formatMoney(a.total, currency)}</td>
              </Row>
            )}
          />
        </>
      )}
    </section>
  );
}
