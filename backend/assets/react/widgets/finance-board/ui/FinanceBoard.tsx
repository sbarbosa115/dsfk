import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {
  fetchFinance,
  financeKey,
  type Finance,
  type Movement,
  type StageFunding,
} from '@/entities/finance';
import {stageTone} from '@/entities/plan';
import {DepositModal} from '@/features/finance-deposit';
import {DrawModal} from '@/features/finance-draw';
import {AttachProofModal} from '@/features/movement-proof';
import {VoidMovementModal} from '@/features/movement-void';
import {CompleteStageModal} from '@/features/stage-complete';
import {t} from '@/shared/i18n';
import {formatMoney, formatPercent} from '@/shared/lib/format';
import {
  ActionButton,
  Actions,
  Alert,
  DataTable,
  EmptyState,
  ErrorState,
  Loading,
  ProgressBar,
  Row,
  RowActions,
  Stat,
  TabIntro,
} from '@/shared/ui';
import {MovementList} from './MovementList';

type Dialog =
  | {kind: 'deposit' | 'draw'}
  | {kind: 'complete'; stage: StageFunding}
  | {kind: 'void' | 'attach'; movement: Movement}
  | null;

/** The "Fondos" tab: where the deposited money is, per stage, petty cash and contingency, and how it got there. */
export function FinanceBoard({projectId}: {projectId: number}) {
  const finance = useQuery({
    queryKey: financeKey(projectId),
    queryFn: () => fetchFinance(projectId),
  });
  const [dialog, setDialog] = useState<Dialog>(null);
  const close = () => setDialog(null);

  if (finance.error) {
    return (
      <ErrorState
        error={finance.error}
        onRetry={() => void finance.refetch()}
      />
    );
  }
  if (!finance.data) {
    return <Loading />;
  }
  const f = finance.data;
  const money = (amount: string) => formatMoney(amount, f.currency);
  const canDraw =
    f.permissions.drawContingency && Number(f.contingency.balance) > 0;

  return (
    <div className="settings-sections">
      <TabIntro
        action={
          f.permissions.deposit ? (
            <>
              {canDraw && (
                <ActionButton
                  action="revert"
                  size="md"
                  onClick={() => setDialog({kind: 'draw'})}
                >
                  {t('finance.draw')}
                </ActionButton>
              )}
              <ActionButton
                action="setup"
                main
                size="md"
                icon="plus"
                onClick={() => setDialog({kind: 'deposit'})}
              >
                {t('finance.newDeposit')}
              </ActionButton>
            </>
          ) : null
        }
      >
        {t('finance.intro')}
      </TabIntro>
      {!f.budgetApproved && (
        <Alert kind="info">{t('finance.notApproved')}</Alert>
      )}

      <section className="card">
        <div className="stat-grid">
          <Stat
            label={t('finance.deposited')}
            value={money(f.totals.deposited)}
            money
          >
            <span className="small muted">
              {t('finance.ofBudget', {budget: money(f.totals.budget)})}
            </span>
          </Stat>
          <Stat label={t('finance.spent')} value={money(f.totals.spent)} money>
            <span className="small muted">
              {t('finance.stagesAvailable', {
                amount: money(f.totals.stagesAvailable),
              })}
            </span>
          </Stat>
          <Stat
            label={t('finance.account.PETTY_CASH')}
            value={money(f.totals.pettyCash)}
            money
          >
            <span className="small muted">
              {t('finance.pettyCashDetail', {
                deposited: money(f.pettyCash.deposited),
              })}
            </span>
          </Stat>
          <Stat
            label={t('finance.account.CONTINGENCY')}
            value={money(f.totals.contingency)}
            money
          >
            <span className="small muted">
              {t('finance.contingencyDetail', {
                budgeted: money(f.contingency.budgeted),
                drawn: money(f.contingency.drawn),
              })}
            </span>
          </Stat>
        </div>
      </section>

      <StagesCard
        finance={f}
        onComplete={(stage) => setDialog({kind: 'complete', stage})}
      />
      {f.categories.length > 0 && <CategoriesCard finance={f} />}

      <section className="card">
        <div className="card-header">
          <h2>{t('finance.movements')}</h2>
        </div>
        <MovementList
          projectId={projectId}
          finance={f}
          onVoid={(movement) => setDialog({kind: 'void', movement})}
          onAttach={(movement) => setDialog({kind: 'attach', movement})}
        />
      </section>

      {dialog?.kind === 'deposit' && (
        <DepositModal projectId={projectId} finance={f} onClose={close} />
      )}
      {dialog?.kind === 'draw' && (
        <DrawModal projectId={projectId} finance={f} onClose={close} />
      )}
      {dialog?.kind === 'complete' && (
        <CompleteStageModal
          projectId={projectId}
          finance={f}
          stage={dialog.stage}
          onClose={close}
        />
      )}
      {dialog?.kind === 'void' && (
        <VoidMovementModal
          projectId={projectId}
          movement={dialog.movement}
          currency={f.currency}
          onClose={close}
        />
      )}
      {dialog?.kind === 'attach' && (
        <AttachProofModal
          projectId={projectId}
          movement={dialog.movement}
          onClose={close}
        />
      )}
    </div>
  );
}

function StagesCard({
  finance: f,
  onComplete,
}: {
  finance: Finance;
  onComplete: (stage: StageFunding) => void;
}) {
  const money = (amount: string) => formatMoney(amount, f.currency);

  return (
    <section className="card">
      <div className="card-header">
        <h2>{t('finance.byStage')}</h2>
      </div>
      <DataTable
        empty={<EmptyState>{t('finance.noStages')}</EmptyState>}
        columns={[
          t('finance.stage'),
          t('finance.budget'),
          t('finance.received'),
          t('finance.funded'),
          t('finance.available'),
        ]}
        rows={f.stages}
        actions={f.permissions.completeStages}
        renderRow={(s) => (
          <Row
            key={s.id}
            status={stageTone(s.status)}
            label={t(`plan.stageStatus.${s.status}`)}
          >
            <td>
              <strong>{s.name}</strong>
              {Number(s.carriedOut) > 0 && (
                <div className="small muted">
                  {t('finance.carriedOut', {amount: money(s.carriedOut)})}
                </div>
              )}
            </td>
            <td className="num">
              {money(s.budget)}
              <div className="small muted cell-note">
                {t('finance.spentOf', {
                  amount: money(s.spent),
                  percent: formatPercent(s.executed),
                })}
              </div>
            </td>
            <td
              className="num"
              title={t('finance.receivedDetail', {
                deposited: money(s.deposited),
                draws: money(s.contingencyDraws),
                carriedIn: money(s.carriedIn),
              })}
            >
              {money(s.received)}
              {Number(s.beyondBudget) > 0 && (
                <div className="small muted cell-note">
                  {t('finance.beyondBudget', {amount: money(s.beyondBudget)})}
                </div>
              )}
            </td>
            <td className="col-progress">
              <ProgressBar
                value={s.funded}
                label={t('finance.fundedOf', {name: s.name})}
              />
            </td>
            <td className="num">
              <strong>{money(s.available)}</strong>
            </td>
            {f.permissions.completeStages && (
              <Actions>
                <RowActions
                  name={s.name}
                  main={
                    s.status === 'IN_PROGRESS' && {
                      label: t('finance.completeStage'),
                      action: 'confirm',
                      onClick: () => onComplete(s),
                    }
                  }
                />
              </Actions>
            )}
          </Row>
        )}
      />
    </section>
  );
}

function CategoriesCard({finance: f}: {finance: Finance}) {
  const money = (amount: string) => formatMoney(amount, f.currency);

  return (
    <section className="card">
      <div className="card-header">
        <h2>{t('finance.byCategory')}</h2>
      </div>
      <DataTable
        columns={[
          t('plan.category'),
          t('finance.budget'),
          t('finance.spent'),
          t('finance.executed'),
        ]}
        rows={f.categories}
        actions={false}
        renderRow={(c) => (
          <tr key={c.id}>
            <td>{c.name}</td>
            <td className="num">{money(c.budget)}</td>
            <td className="num">{money(c.spent)}</td>
            <td>
              <ProgressBar
                value={c.executed}
                label={t('finance.executedOf', {name: c.name})}
              />
            </td>
          </tr>
        )}
      />
    </section>
  );
}
