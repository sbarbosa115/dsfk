import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {attachmentUrl} from '@/entities/finance';
import {
  cycleKey,
  cycleTone,
  fetchCycle,
  fetchPettyCash,
  pettyCashKey,
  type Cycle,
  type CycleMovement,
} from '@/entities/petty-cash';
import {CloseCycleModal} from '@/features/cycle-close';
import {SignOffCycleModal} from '@/features/cycle-signoff';
import {t} from '@/shared/i18n';
import {formatDate, formatDateTime, formatMoney} from '@/shared/lib/format';
import {
  ActionButton,
  actionClass,
  Actions,
  DataTable,
  DefinitionList,
  EmptyState,
  ErrorState,
  IconButton,
  Loading,
  Modal,
  Row,
  RowLegend,
  Stat,
  TabIntro,
} from '@/shared/ui';

type Dialog = {kind: 'close'} | {kind: 'view' | 'signOff'; cycle: Cycle} | null;

const CYCLE_STATUSES = ['OPEN', 'CLOSED', 'SIGNED_OFF'] as const;

/** The "Caja menor" tab: its balance, what moved in the current cycle, and the closed cycles an Admin signs off. */
export function PettyCashBoard({projectId}: {projectId: number}) {
  const cash = useQuery({
    queryKey: pettyCashKey(projectId),
    queryFn: () => fetchPettyCash(projectId),
  });
  const [dialog, setDialog] = useState<Dialog>(null);
  const close = () => setDialog(null);

  if (cash.error) {
    return (
      <ErrorState error={cash.error} onRetry={() => void cash.refetch()} />
    );
  }
  if (!cash.data) {
    return <Loading />;
  }
  const c = cash.data;
  const money = (amount: string) => formatMoney(amount, c.currency);
  const current = c.current;

  return (
    <div className="settings-sections">
      <TabIntro
        action={
          c.permissions.close ? (
            <ActionButton
              action="confirm"
              main
              size="md"
              onClick={() => setDialog({kind: 'close'})}
            >
              {t('pettyCash.close')}
            </ActionButton>
          ) : null
        }
      >
        {t('pettyCash.intro')}
      </TabIntro>

      <section className="card">
        <div className="stat-grid">
          <Stat label={t('pettyCash.balance')} value={money(c.balance)} money />
          <Stat
            label={t('pettyCash.topUps')}
            value={money(current.topUps)}
            money
          >
            <span className="small muted">
              {t('pettyCash.openingOf', {
                amount: money(current.openingBalance),
              })}
            </span>
          </Stat>
          <Stat label={t('pettyCash.spent')} value={money(current.spent)} money>
            <span className="small muted">
              {t('pettyCash.reimbursedOf', {amount: money(current.reimbursed)})}
            </span>
          </Stat>
          <Stat label={t('pettyCash.unsigned')} value={c.unsignedCount} />
        </div>
      </section>

      <section className="card">
        <div className="card-header">
          <h2>{t('pettyCash.currentCycle', {number: current.number})}</h2>
          {current.openedAt && (
            <span className="small muted">
              {t('pettyCash.openedOn', {date: formatDate(current.openedAt)})}
            </span>
          )}
        </div>
        <Movements movements={current.movements ?? []} money={money} />
      </section>

      <section className="card">
        <div className="card-header">
          <h2>{t('pettyCash.history')}</h2>
        </div>
        {c.history.length === 0 ? (
          <EmptyState>{t('pettyCash.noHistory')}</EmptyState>
        ) : (
          <>
            <RowLegend
              statuses={CYCLE_STATUSES.map((s) => ({
                value: cycleTone(s),
                label: t(`pettyCash.status.${s}`),
              }))}
            />
            <DataTable
              columns={[
                t('pettyCash.cycle'),
                t('pettyCash.closed'),
                t('pettyCash.opening'),
                t('pettyCash.topUps'),
                t('pettyCash.outflows'),
                t('pettyCash.closing'),
              ]}
              rows={c.history}
              renderRow={(cycle) => (
                <Row
                  key={cycle.id}
                  status={cycleTone(cycle.status)}
                  label={t(`pettyCash.status.${cycle.status}`)}
                >
                  <td>
                    <strong>#{cycle.number}</strong>
                  </td>
                  <td>
                    {formatDate(cycle.closedAt)}
                    {cycle.closedBy && (
                      <div className="small muted">{cycle.closedBy}</div>
                    )}
                  </td>
                  <td className="num">{money(cycle.openingBalance)}</td>
                  <td className="num">{money(cycle.topUps)}</td>
                  <td className="num">
                    {money(
                      String(Number(cycle.spent) + Number(cycle.reimbursed)),
                    )}
                  </td>
                  <td className="num">
                    <strong>{money(cycle.closingBalance)}</strong>
                  </td>
                  <Actions>
                    <IconButton
                      icon="eye"
                      label={t('pettyCash.viewCycle', {number: cycle.number})}
                      onClick={() => setDialog({kind: 'view', cycle})}
                    />
                    {c.permissions.signOff && cycle.status === 'CLOSED' && (
                      <ActionButton
                        action="confirm"
                        onClick={() => setDialog({kind: 'signOff', cycle})}
                      >
                        {t('pettyCash.signOff')}
                      </ActionButton>
                    )}
                  </Actions>
                </Row>
              )}
            />
          </>
        )}
      </section>

      {dialog?.kind === 'close' && (
        <CloseCycleModal projectId={projectId} pettyCash={c} onClose={close} />
      )}
      {dialog?.kind === 'signOff' && (
        <SignOffCycleModal
          projectId={projectId}
          cycle={dialog.cycle}
          currency={c.currency}
          onClose={close}
        />
      )}
      {dialog?.kind === 'view' && dialog.cycle.id !== null && (
        <CycleModal cycleId={dialog.cycle.id!} money={money} onClose={close} />
      )}
    </div>
  );
}

function Movements({
  movements,
  money,
}: {
  movements: CycleMovement[];
  money: (amount: string) => string;
}) {
  if (movements.length === 0) {
    return <EmptyState>{t('pettyCash.noMovements')}</EmptyState>;
  }

  return (
    <DataTable
      columns={[
        t('finance.date'),
        t('finance.movement'),
        t('pettyCash.who'),
        t('finance.amount'),
      ]}
      rows={movements}
      renderRow={(m) => (
        <Row
          key={m.id}
          muted={m.voided}
          status={m.voided ? 'movement_voided' : null}
          label={m.voided ? t('finance.voided') : null}
        >
          <td>{formatDate(m.date)}</td>
          <td>
            <strong>{t(`finance.type.${m.type}`)}</strong>
            {(m.description || m.method) && (
              <div className="small muted">
                {[
                  m.method && t(`finance.methods.${m.method}`),
                  m.reference,
                  m.description,
                ]
                  .filter(Boolean)
                  .join(' · ')}
              </div>
            )}
          </td>
          <td>{m.user}</td>
          <td className="num">{money(m.amount)}</td>
          <Actions>
            {m.attachments.map((a) => (
              <a
                key={a.id}
                className={actionClass('file')}
                href={attachmentUrl(a.id)}
                target="_blank"
                rel="noopener noreferrer"
                title={a.name}
              >
                {m.type === 'EXPENSE'
                  ? t('expenses.receipt')
                  : t('finance.proof')}
              </a>
            ))}
          </Actions>
        </Row>
      )}
    />
  );
}

function CycleModal({
  cycleId,
  money,
  onClose,
}: {
  cycleId: number;
  money: (amount: string) => string;
  onClose: () => void;
}) {
  const cycle = useQuery({
    queryKey: cycleKey(cycleId),
    queryFn: () => fetchCycle(cycleId),
  });
  const c = cycle.data;

  return (
    <Modal
      title={t('pettyCash.cycleTitle', {number: c?.number ?? ''})}
      onClose={onClose}
      size="wide"
    >
      {cycle.error ? (
        <ErrorState error={cycle.error} />
      ) : !c ? (
        <Loading />
      ) : (
        <>
          <DefinitionList
            items={[
              [t('common.status'), t(`pettyCash.status.${c.status}`)],
              [t('pettyCash.opening'), money(c.openingBalance)],
              [t('pettyCash.topUps'), money(c.topUps)],
              [t('pettyCash.spent'), money(c.spent)],
              [t('pettyCash.reimbursed'), money(c.reimbursed)],
              [t('pettyCash.closing'), money(c.closingBalance)],
              c.closedAt && [
                t('pettyCash.closed'),
                `${formatDateTime(c.closedAt)} · ${c.closedBy ?? ''}`,
              ],
              c.closingNote && [t('pettyCash.closingNote'), c.closingNote],
              c.signedOffAt && [
                t('pettyCash.signedOff'),
                `${formatDateTime(c.signedOffAt)} · ${c.signedOffBy ?? ''}`,
              ],
            ]}
          />
          <Movements movements={c.movements ?? []} money={money} />
        </>
      )}
    </Modal>
  );
}
