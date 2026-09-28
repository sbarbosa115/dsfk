import {useState} from 'react';
import {
  milestoneLabel,
  milestoneTone,
  stageTone,
  type Plan,
  type PlanLine,
  type PlanStage,
} from '@/entities/plan';
import {DeleteLineModal, LineFormModal} from '@/features/plan-lines';
import {MilestoneActions, MilestoneFormModal} from '@/features/plan-milestones';
import {StageActions} from '@/features/plan-stages';
import {t} from '@/shared/i18n';
import {
  formatDate,
  formatMoney,
  formatPercent,
  formatQuantity,
} from '@/shared/lib/format';
import {
  ActionButton,
  Actions,
  Badge,
  DataTable,
  EmptyState,
  IconButton,
  ProgressBar,
  Row,
  RowLegend,
} from '@/shared/ui';

type Dialog =
  | {kind: 'addLine'}
  | {kind: 'editLine' | 'deleteLine'; line: PlanLine}
  | {kind: 'addMilestone'}
  | null;

/** One stage: header with its status, dates, money and progress; then its lines and its milestones. */
export function StageCard({
  plan,
  stage,
  index,
}: {
  plan: Plan;
  stage: PlanStage;
  index: number;
}) {
  const [dialog, setDialog] = useState<Dialog>(null);
  const close = () => setDialog(null);
  const {edit} = plan.permissions;
  const currency = plan.project.currency;
  const categoryName = (id: number) =>
    plan.categories.find((c) => c.id === id)?.name ?? '';
  const weightsOk = stage.milestoneWeightTotal === 10000;

  return (
    <section className="card" aria-label={stage.name}>
      <div className="stage-header">
        <div>
          <div className="stage-title">
            <span>
              {index + 1}. {stage.name}
            </span>
            <Badge value={stageTone(stage.status)}>
              {t(`plan.stageStatus.${stage.status}`)}
            </Badge>
          </div>
          <div className="small muted nowrap">
            {formatDate(stage.plannedStart)} – {formatDate(stage.plannedEnd)}
            {stage.actualStart &&
              ` · ${t('plan.started', {date: formatDate(stage.actualStart)})}`}
            {stage.actualEnd &&
              ` · ${t('plan.finished', {date: formatDate(stage.actualEnd)})}`}
          </div>
        </div>
        <div>
          {stage.budgetTotal != null && (
            <>
              <div className="strong nowrap">
                {formatMoney(stage.budgetTotal, currency)}
              </div>
              <div className="small muted">
                {t('plan.shareOfBudget', {
                  percent: formatPercent(stage.weight ?? 0),
                })}
              </div>
            </>
          )}
        </div>
        <ProgressBar
          value={stage.progress}
          label={`${t('plan.progress')}: ${stage.name}`}
        />
      </div>
      <StageActions plan={plan} stage={stage} index={index} />

      {stage.lines != null && (
        <>
          <div className="section-title">
            <h3>{t('plan.lines')}</h3>
            {edit && (
              <ActionButton
                action="setup"
                disabled={plan.categories.length === 0}
                onClick={() => setDialog({kind: 'addLine'})}
              >
                {t('plan.addLine')}
              </ActionButton>
            )}
          </div>
          {edit && plan.categories.length === 0 && (
            <p className="small text-danger">{t('plan.noCategories')}</p>
          )}
          {stage.lines.length === 0 ? (
            <EmptyState>{t('plan.noLines')}</EmptyState>
          ) : (
            <DataTable
              columns={[
                t('plan.category'),
                t('plan.description'),
                t('plan.quantity'),
                t('plan.unitPrice'),
                t('plan.lineTotal'),
              ]}
              actions={edit}
              rows={stage.lines}
              renderRow={(line) => (
                <tr key={line.id}>
                  <td>{categoryName(line.categoryId)}</td>
                  <td>{line.description}</td>
                  <td className="num">
                    {formatQuantity(line.quantity)} {line.unit}
                  </td>
                  <td className="num">
                    {formatMoney(line.unitPrice, currency)}
                  </td>
                  <td className="num">{formatMoney(line.total, currency)}</td>
                  {edit && (
                    <Actions>
                      <IconButton
                        icon="pencil"
                        label={t('plan.editLine')}
                        onClick={() => setDialog({kind: 'editLine', line})}
                      />
                      <ActionButton
                        action="danger"
                        onClick={() => setDialog({kind: 'deleteLine', line})}
                      >
                        {t('plan.remove')}
                      </ActionButton>
                    </Actions>
                  )}
                </tr>
              )}
            />
          )}
        </>
      )}

      <div className="section-title">
        <h3>{t('plan.milestones')}</h3>
        <span className={`small ${weightsOk ? 'muted' : 'text-danger'}`}>
          {t('plan.weightTotal', {
            total: formatPercent(stage.milestoneWeightTotal),
          })}
        </span>
        {edit && (
          <ActionButton
            action="setup"
            disabled={stage.milestoneWeightTotal >= 10000}
            onClick={() => setDialog({kind: 'addMilestone'})}
          >
            {t('plan.addMilestone')}
          </ActionButton>
        )}
      </div>
      {stage.milestones.length === 0 ? (
        <EmptyState>{t('plan.noMilestones')}</EmptyState>
      ) : (
        <>
          <RowLegend
            statuses={[
              {
                value: 'milestone_done',
                label: t('plan.milestoneState.milestone_done'),
              },
              {
                value: 'milestone_overdue',
                label: t('plan.milestoneState.milestone_overdue'),
              },
            ]}
          />
          <DataTable
            columns={[
              t('plan.milestoneName'),
              t('plan.weight'),
              t('plan.plannedDate'),
              t('plan.completed'),
            ]}
            actions={
              edit ||
              plan.permissions.track ||
              plan.permissions.reopenMilestones
            }
            rows={stage.milestones}
            renderRow={(milestone) => (
              <Row
                key={milestone.id}
                status={milestoneTone(milestone)}
                label={milestoneLabel(milestone)}
              >
                <td className="strong">{milestone.name}</td>
                <td className="num">{formatPercent(milestone.weight)}</td>
                <td className="nowrap">{formatDate(milestone.plannedDate)}</td>
                <td>
                  {milestone.completedAt ? (
                    <>
                      <div className="nowrap">
                        {t('plan.completedBy', {
                          date: formatDate(milestone.completedAt),
                          user: milestone.completedBy?.fullName ?? '',
                        })}
                      </div>
                      {milestone.completionNotes && (
                        <div className="small muted">
                          {milestone.completionNotes}
                        </div>
                      )}
                    </>
                  ) : (
                    <span className="muted">—</span>
                  )}
                </td>
                {(edit ||
                  plan.permissions.track ||
                  plan.permissions.reopenMilestones) && (
                  <Actions>
                    <MilestoneActions
                      plan={plan}
                      stage={stage}
                      milestone={milestone}
                    />
                  </Actions>
                )}
              </Row>
            )}
          />
        </>
      )}

      {dialog?.kind === 'addLine' && (
        <LineFormModal plan={plan} stage={stage} onClose={close} />
      )}
      {dialog?.kind === 'editLine' && (
        <LineFormModal
          plan={plan}
          stage={stage}
          line={dialog.line}
          onClose={close}
        />
      )}
      {dialog?.kind === 'deleteLine' && (
        <DeleteLineModal line={dialog.line} onClose={close} />
      )}
      {dialog?.kind === 'addMilestone' && (
        <MilestoneFormModal stage={stage} onClose={close} />
      )}
    </section>
  );
}
