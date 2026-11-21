import {useState} from 'react';
import {
  budgetTone,
  daysBetween,
  lateMilestones,
  milestoneLabel,
  milestoneTone,
  nextMilestone,
  stageTimeShare,
  stageTone,
  type Plan,
  type PlanLine,
  type PlanMilestone,
  type PlanStage,
} from '@/entities/plan';
import {DeleteLineModal, LineFormModal} from '@/features/plan-lines';
import {MilestoneActions, MilestoneFormModal} from '@/features/plan-milestones';
import {StageActions} from '@/features/plan-stages';
import {t} from '@/shared/i18n';
import {
  formatDate,
  formatDateRange,
  formatMoney,
  formatPercent,
  formatQuantity,
  today,
} from '@/shared/lib/format';
import {
  ActionButton,
  Actions,
  Badge,
  DataTable,
  EmptyState,
  Icon,
  ProgressBar,
  RowActions,
  Tabs,
  toneFor,
} from '@/shared/ui';

type Dialog =
  | {kind: 'addLine'}
  | {kind: 'editLine' | 'deleteLine'; line: PlanLine}
  | {kind: 'addMilestone'}
  | null;

type Tab = 'milestones' | 'lines';

/**
 * One stage, in two layers. Its summary is always in view: number, name, status, dates, money, physical progress
 * against the time gone, milestones met and late, and its own actions. Its detail opens on demand, with the
 * milestones (a checklist) and the lines (largest first) in two tabs. A stage added to the approved budget shows its
 * addition (Adición) until the Admin approves it.
 */
export function StageCard({
  plan,
  stage,
  index,
  open,
  onToggle,
}: {
  plan: Plan;
  stage: PlanStage;
  index: number;
  open: boolean;
  onToggle: () => void;
}) {
  const [dialog, setDialog] = useState<Dialog>(null);
  // A stage being written starts on its lines, where a budget is built; once approved, on what is tracked.
  const [tab, setTab] = useState<Tab>(
    stage.editable && stage.lines != null ? 'lines' : 'milestones',
  );
  const close = () => setDialog(null);
  const addition =
    stage.amendment != null && !stage.approved
      ? plan.budget?.amendments.find((a) => a.number === stage.amendment)
      : undefined;
  const currency = plan.project.currency;
  const now = today();
  const timeShare = stageTimeShare(stage, now);
  const behind = timeShare !== null && stage.progress < timeShare;
  const late = lateMilestones(stage).length;
  const met = stage.milestones.filter((m) => m.completedAt).length;
  const detailId = `stage-${stage.id}-detail`;
  const shown: Tab = stage.lines == null ? 'milestones' : tab;

  return (
    <section className="card stage-card" aria-label={stage.name}>
      <div className="stage-summary">
        <span
          className={`stage-number badge-${toneFor(stageTone(stage.status))}`}
          aria-hidden="true"
        >
          {index + 1}
        </span>

        <div className="stage-main">
          <div className="stage-title">
            <h4>{stage.name}</h4>
            {stage.approved ? (
              <Badge value={stageTone(stage.status)}>
                {t(`plan.stageStatus.${stage.status}`)}
              </Badge>
            ) : (
              <Badge value={budgetTone(addition?.status ?? 'SUBMITTED')}>
                {t('plan.amendment.badge', {number: stage.amendment})}
                {' · '}
                {addition
                  ? t(`plan.amendment.status.${addition.status}`)
                  : t('plan.amendment.pending')}
              </Badge>
            )}
          </div>
          <div className="small muted">
            {formatDateRange(stage.plannedStart, stage.plannedEnd)}
            {stage.actualStart &&
              ` · ${t('plan.started', {date: formatDate(stage.actualStart)})}`}
            {stage.actualEnd &&
              ` · ${t('plan.finished', {date: formatDate(stage.actualEnd)})}`}
          </div>
          {/* The stage's own controls, under its name rather than loose below the header (QA-0014). */}
          <StageActions plan={plan} stage={stage} index={index} />
        </div>

        {stage.budgetTotal != null && (
          <div className="stage-figure">
            <span className="small muted">{t('plan.stageBudget')}</span>
            <span className="strong nowrap">
              {formatMoney(stage.budgetTotal, currency)}
            </span>
            <span className="small muted">
              {stage.approved
                ? t('plan.shareOfBudget', {
                    percent: formatPercent(stage.weight ?? 0),
                  })
                : t('plan.amendment.pending')}
            </span>
          </div>
        )}

        <div className="stage-progress">
          <span className="small muted">{t('plan.physicalProgress')}</span>
          <ProgressBar
            value={stage.progress}
            label={`${t('plan.progress')}: ${stage.name}`}
            marker={timeShare}
            markerLabel={
              timeShare === null
                ? undefined
                : t('plan.timeElapsed', {percent: formatPercent(timeShare)})
            }
          />
          {timeShare !== null && (
            <span className={`small ${behind ? 'stage-pace-behind' : 'muted'}`}>
              {t('plan.pace', {
                progress: formatPercent(stage.progress),
                time: formatPercent(timeShare),
              })}
            </span>
          )}
        </div>

        <div className="stage-figure">
          <span className="small muted">{t('plan.milestones')}</span>
          <span className="strong">
            {t('plan.milestonesMet', {
              met,
              total: stage.milestones.length,
            })}
          </span>
          {late > 0 && (
            <Badge value="milestone_overdue">
              {t('plan.lateMilestones', {count: late})}
            </Badge>
          )}
        </div>

        <ActionButton
          action="open"
          className="stage-toggle"
          aria-expanded={open}
          aria-controls={open ? detailId : undefined}
          onClick={onToggle}
        >
          {open ? t('plan.hideDetail') : t('plan.showDetail')}
          <Icon name="chevronDown" size={16} />
        </ActionButton>
      </div>

      {open && (
        <div className="stage-detail" id={detailId}>
          {stage.lines != null && (
            <Tabs<Tab>
              value={shown}
              label={t('plan.stageContent', {name: stage.name})}
              options={[
                {
                  value: 'milestones',
                  label: t('plan.tabMilestones', {
                    count: stage.milestones.length,
                  }),
                },
                {
                  value: 'lines',
                  label: t('plan.tabLines', {count: stage.lines.length}),
                },
              ]}
              onChange={setTab}
            />
          )}
          {shown === 'milestones' ? (
            <Milestones
              plan={plan}
              stage={stage}
              now={now}
              onAdd={() => setDialog({kind: 'addMilestone'})}
            />
          ) : (
            <Lines
              plan={plan}
              stage={stage}
              onAdd={() => setDialog({kind: 'addLine'})}
              onEdit={(line) => setDialog({kind: 'editLine', line})}
              onDelete={(line) => setDialog({kind: 'deleteLine', line})}
            />
          )}
        </div>
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

/** The stage's milestones as a checklist: met ones ticked, late ones in red with how late, the next one tagged. */
function Milestones({
  plan,
  stage,
  now,
  onAdd,
}: {
  plan: Plan;
  stage: PlanStage;
  now: string;
  onAdd: () => void;
}) {
  const edit = stage.editable;
  const weightsOk = stage.milestoneWeightTotal === 10000;
  const next = nextMilestone(stage);
  const canAct =
    edit ||
    (plan.permissions.track && stage.approved) ||
    plan.permissions.reopenMilestones;

  return (
    <>
      <div className="section-title">
        <div className="section-title-text">
          <h5>{t('plan.milestones')}</h5>
          <span className={`small ${weightsOk ? 'muted' : 'text-danger'}`}>
            {t('plan.weightTotal', {
              total: formatPercent(stage.milestoneWeightTotal),
            })}
          </span>
        </div>
        {edit && (
          <ActionButton
            action="setup"
            disabled={stage.milestoneWeightTotal >= 10000}
            // A disabled button says why (QA-0014).
            title={
              stage.milestoneWeightTotal >= 10000
                ? t('plan.weightsFull')
                : undefined
            }
            onClick={onAdd}
          >
            {t('plan.addMilestone')}
          </ActionButton>
        )}
      </div>
      {stage.milestones.length === 0 ? (
        <EmptyState>{t('plan.noMilestones')}</EmptyState>
      ) : (
        <ul className="milestone-list">
          {stage.milestones.map((milestone) => (
            <MilestoneItem
              key={milestone.id}
              plan={plan}
              stage={stage}
              milestone={milestone}
              next={next?.id === milestone.id}
              now={now}
              actions={canAct}
            />
          ))}
        </ul>
      )}
    </>
  );
}

function MilestoneItem({
  plan,
  stage,
  milestone,
  next,
  now,
  actions,
}: {
  plan: Plan;
  stage: PlanStage;
  milestone: PlanMilestone;
  next: boolean;
  now: string;
  actions: boolean;
}) {
  const tone = milestoneTone(milestone);
  const label = milestoneLabel(milestone);
  const classes = [
    'milestone-item',
    tone && `milestone-item-${toneFor(tone)}`,
    next && 'milestone-item-next',
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <li className={classes}>
      <span className="milestone-check" aria-hidden="true">
        {milestone.completedAt && <Icon name="check" size={14} />}
      </span>
      <div className="milestone-body">
        <div className="milestone-name">
          {label && <span className="visually-hidden">{`${label}: `}</span>}
          {milestone.name}
          {next && <Badge value="info">{t('plan.nextMilestone')}</Badge>}
        </div>
        <div
          className={`small milestone-state milestone-state-${tone ?? 'plain'}`}
        >
          {milestoneState(milestone, now)}
        </div>
        {milestone.completionNotes && (
          <div className="small muted">{milestone.completionNotes}</div>
        )}
      </div>
      <span className="milestone-weight">
        {t('plan.milestoneWeight', {weight: formatPercent(milestone.weight)})}
      </span>
      {actions && (
        <div className="milestone-actions">
          <MilestoneActions
            plan={plan}
            stage={stage}
            milestone={milestone}
            quiet
          />
        </div>
      )}
    </li>
  );
}

/** What a milestone's date means today, in words. */
function milestoneState(milestone: PlanMilestone, now: string): string {
  if (milestone.completedAt) {
    return t('plan.completedOn', {
      date: formatDate(milestone.completedAt),
      user: milestone.completedBy?.fullName ?? '',
    });
  }
  if (!milestone.plannedDate) {
    return t('plan.noPlannedDate');
  }
  const date = formatDate(milestone.plannedDate);
  if (milestone.overdue) {
    const days = daysBetween(milestone.plannedDate, now);
    return days <= 0
      ? t('plan.dueToday', {date})
      : t('plan.lateBy', {count: days, date});
  }

  return t('plan.plannedFor', {date});
}

/** The stage's lines, largest first, each with its share of the stage. */
function Lines({
  plan,
  stage,
  onAdd,
  onEdit,
  onDelete,
}: {
  plan: Plan;
  stage: PlanStage;
  onAdd: () => void;
  onEdit: (line: PlanLine) => void;
  onDelete: (line: PlanLine) => void;
}) {
  const edit = stage.editable;
  const currency = plan.project.currency;
  const categoryName = (id: number) =>
    plan.categories.find((c) => c.id === id)?.name ?? '';
  const stageTotal = Number(stage.budgetTotal ?? 0);
  const lines = [...(stage.lines ?? [])].sort(
    (a, b) => Number(b.total) - Number(a.total),
  );

  return (
    <>
      <div className="section-title">
        <h5>{t('plan.lines')}</h5>
        {edit && (
          <ActionButton
            action="setup"
            disabled={plan.categories.length === 0}
            onClick={onAdd}
          >
            {t('plan.addLine')}
          </ActionButton>
        )}
      </div>
      {edit && plan.categories.length === 0 && (
        <p className="small text-danger">{t('plan.noCategories')}</p>
      )}
      <DataTable
        empty={<EmptyState>{t('plan.noLines')}</EmptyState>}
        columns={[
          t('plan.line'),
          t('plan.quantity'),
          t('plan.unitPrice'),
          t('plan.lineTotal'),
          t('plan.lineShare'),
        ]}
        actions={edit}
        rows={lines}
        renderRow={(line) => {
          // Basis points of the stage: the bar's width and the figure beside it.
          const share =
            stageTotal > 0
              ? Math.round((Number(line.total) / stageTotal) * 10000)
              : 0;
          return (
            <tr key={line.id}>
              <td>
                <div className="strong">{line.description}</div>
                <div className="small muted">
                  {categoryName(line.categoryId)}
                </div>
              </td>
              <td className="num">
                {formatQuantity(line.quantity)} {line.unit}
              </td>
              <td className="num">{formatMoney(line.unitPrice, currency)}</td>
              <td className="num strong">
                {formatMoney(line.total, currency)}
              </td>
              <td>
                <ProgressBar
                  value={share}
                  label={`${t('plan.lineShare')}: ${line.description}`}
                />
              </td>
              {edit && (
                <Actions>
                  <RowActions
                    name={line.description}
                    edit={{onClick: () => onEdit(line)}}
                    more={[
                      {
                        items: [
                          {
                            label: t('plan.remove'),
                            action: 'danger',
                            icon: 'ban',
                            onClick: () => onDelete(line),
                          },
                        ],
                      },
                    ]}
                  />
                </Actions>
              )}
            </tr>
          );
        }}
      />
    </>
  );
}
