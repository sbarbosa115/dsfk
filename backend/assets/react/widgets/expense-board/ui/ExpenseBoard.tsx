import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {
  EXPENSE_STATUSES,
  expenseStatusLabel,
  expenseTone,
  expensesKey,
  expensesPath,
  paidFromLabel,
  type Expense,
  type ExpensePage,
} from '@/entities/expense';
import {attachmentUrl} from '@/entities/finance';
import {fetchPlan, planKey, type Plan} from '@/entities/plan';
import {ExpenseFormModal} from '@/features/expense-form';
import {AttachReceiptModal} from '@/features/expense-receipt';
import {ReimburseModal} from '@/features/expense-reimburse';
import {
  ApproveExpenseModal,
  RejectExpenseModal,
} from '@/features/expense-review';
import {VoidExpenseModal} from '@/features/expense-void';
import {t} from '@/shared/i18n';
import {formatDate, formatMoney} from '@/shared/lib/format';
import {useList} from '@/shared/lib/list';
import {
  ActionButton,
  Actions,
  Alert,
  ErrorState,
  FilterBar,
  ListView,
  Loading,
  Row,
  RowActions,
  RowLegend,
  Stat,
  TabIntro,
  type RowAction,
} from '@/shared/ui';
import {expenseRowActions, type ExpenseRowAction} from '../model/rowActions';
import {ExpenseDetailModal} from './ExpenseDetailModal';

type Dialog =
  | {kind: 'record' | 'reimburse'}
  | {
      kind: 'view' | 'edit' | 'approve' | 'reject' | 'void' | 'attach';
      expense: Expense;
    }
  | null;

type Filters = {q: string; status: string; stageId: string};

/** Offer "Adjuntar recibo" while the expense has none or is still being decided; the detail shows the rest. */
type RowDialog = 'view' | 'edit' | 'approve' | 'reject' | 'attach' | 'void';

/** An expense's row: its main action and the menu with the rest (model/rowActions decides which). */
function ExpenseActions({
  expense: e,
  open,
}: {
  expense: Expense;
  open: (kind: RowDialog) => void;
}) {
  const plan = expenseRowActions(e);
  const receipt = e.attachments[0];
  const actions: Record<ExpenseRowAction, RowAction> = {
    approve: {
      label: t('expenses.approve'),
      action: 'confirm',
      onClick: () => open('approve'),
    },
    attach: {
      label: t('expenses.addReceipt'),
      action: 'setup',
      icon: 'paperclip',
      onClick: () => open('attach'),
    },
    edit: {
      label: t('expenses.correct'),
      action: 'edit',
      icon: 'pencil',
      onClick: () => open('edit'),
    },
    view: {
      label: t('expenses.view'),
      action: 'open',
      icon: 'eye',
      onClick: () => open('view'),
    },
    receipt: {
      label: t('expenses.receipt'),
      action: 'file',
      icon: 'file',
      description: receipt?.name,
      href: receipt ? attachmentUrl(receipt.id) : undefined,
    },
    reject: {
      label: t('expenses.reject'),
      action: 'danger',
      icon: 'ban',
      onClick: () => open('reject'),
    },
    void: {
      label: t('finance.void'),
      action: 'danger',
      icon: 'ban',
      onClick: () => open('void'),
    },
  };
  return (
    <RowActions
      name={e.description}
      main={actions[plan.main]}
      more={[
        {
          title: t('expenses.rowGroup'),
          items: plan.items.map((a) => actions[a]),
        },
        {items: plan.last.map((a) => actions[a])},
      ]}
    />
  );
}

/** The "Gastos" tab: every expense for the PM and Admins, a Team Lead's own for a Team Lead. */
export function ExpenseBoard({
  projectId,
  manager,
}: {
  projectId: number;
  manager: boolean;
}) {
  const plan = useQuery({
    queryKey: planKey(projectId),
    queryFn: () => fetchPlan(projectId),
  });

  if (plan.error) {
    return (
      <ErrorState error={plan.error} onRetry={() => void plan.refetch()} />
    );
  }
  if (!plan.data) {
    return <Loading />;
  }

  return <Board plan={plan.data} manager={manager} />;
}

function Board({plan, manager}: {plan: Plan; manager: boolean}) {
  const projectId = plan.project.id;
  const currency = plan.project.currency;
  const list = useList<Expense, Filters>(
    expensesKey(projectId),
    expensesPath(projectId),
    {
      q: '',
      status: '',
      stageId: '',
    },
  );
  const [dialog, setDialog] = useState<Dialog>(null);
  const close = () => setDialog(null);
  const money = (amount: string) => formatMoney(amount, currency);
  const summary = (list.data as ExpensePage | null)?.summary;
  const approved = plan.budgetStatus === 'APPROVED';

  return (
    <div className="settings-sections">
      <TabIntro
        action={
          approved ? (
            <>
              {manager && (summary?.toReimburseCount ?? 0) > 0 && (
                <ActionButton
                  action="confirm"
                  size="md"
                  onClick={() => setDialog({kind: 'reimburse'})}
                >
                  {t('expenses.reimburse')}
                </ActionButton>
              )}
              <ActionButton
                action="setup"
                main
                size="md"
                icon="plus"
                onClick={() => setDialog({kind: 'record'})}
              >
                {t('expenses.record')}
              </ActionButton>
            </>
          ) : null
        }
      >
        {manager ? t('expenses.intro') : t('expenses.introTeamLead')}
      </TabIntro>
      {!approved && <Alert kind="info">{t('expenses.notApproved')}</Alert>}

      {summary && (
        <section className="card">
          <div className="stat-grid">
            <Stat label={t('expenses.pending')} value={summary.pendingCount}>
              <span className="small muted">{money(summary.pendingTotal)}</span>
            </Stat>
            <Stat
              label={t('expenses.toReimburse')}
              value={summary.toReimburseCount}
            >
              <span className="small muted">
                {money(summary.toReimburseTotal)}
              </span>
            </Stat>
            <Stat
              label={t('expenses.teamLeadLimit')}
              value={money(summary.teamLeadLimit)}
              money
            >
              <span className="small muted">
                {t('expenses.teamLeadLimitHint')}
              </span>
            </Stat>
          </div>
        </section>
      )}

      <section className="card">
        <FilterBar
          search={list.filters.q}
          onSearch={(q) => list.update({q})}
          searchPlaceholder={t('expenses.search')}
          filters={[
            {
              name: 'status',
              label: t('common.status'),
              value: list.filters.status,
              onChange: (status) => list.update({status}),
              options: [
                {value: '', label: t('common.all')},
                ...EXPENSE_STATUSES.map((s) => ({
                  value: s,
                  label: expenseStatusLabel(s),
                })),
              ],
            },
            {
              name: 'stageId',
              label: t('finance.stage'),
              value: list.filters.stageId,
              onChange: (stageId) => list.update({stageId}),
              options: [
                {value: '', label: t('common.all')},
                ...plan.stages.map((s) => ({
                  value: String(s.id),
                  label: s.name,
                })),
              ],
            },
          ]}
        />
        <RowLegend
          statuses={EXPENSE_STATUSES.map((s) => ({
            value: expenseTone(s),
            label: expenseStatusLabel(s),
          }))}
        />
        <ListView
          list={list}
          showAll={{status: '', stageId: ''}}
          empty={t('expenses.noMatches')}
          emptyAll={t('expenses.none')}
          columns={[
            t('finance.date'),
            t('expenses.expense'),
            t('expenses.where'),
            t('expenses.paid'),
            t('finance.amount'),
          ]}
          renderRow={(e) => (
            <Row
              key={e.id}
              status={expenseTone(e.status)}
              label={expenseStatusLabel(e.status)}
            >
              <td className="nowrap">{formatDate(e.date)}</td>
              <td>
                <strong>{e.description}</strong>
                {(e.supplier || e.invoiceNumber) && (
                  <div className="small muted">
                    {[e.supplier, e.invoiceNumber].filter(Boolean).join(' · ')}
                  </div>
                )}
                {e.status === 'REJECTED' && e.rejectionReason && (
                  <div className="small">
                    {t('expenses.rejectedBecause', {reason: e.rejectionReason})}
                  </div>
                )}
                {e.attachments.length === 0 &&
                  (e.status === 'SUBMITTED' || e.status === 'PM_APPROVED') && (
                    <div className="small muted">{t('expenses.noReceipt')}</div>
                  )}
              </td>
              <td>
                {e.stage.name}
                <div className="small muted">{e.category.name}</div>
              </td>
              <td>{paidFromLabel(e)}</td>
              <td className="num">{money(e.amount)}</td>
              <Actions>
                <ExpenseActions
                  expense={e}
                  open={(kind) => setDialog({kind, expense: e})}
                />
              </Actions>
            </Row>
          )}
        />
      </section>

      {dialog?.kind === 'record' && (
        <ExpenseFormModal plan={plan} manager={manager} onClose={close} />
      )}
      {dialog?.kind === 'edit' && (
        <ExpenseFormModal
          plan={plan}
          manager={manager}
          expense={dialog.expense}
          onClose={close}
        />
      )}
      {dialog?.kind === 'view' && (
        <ExpenseDetailModal
          expenseId={dialog.expense.id}
          currency={currency}
          onClose={close}
        />
      )}
      {dialog?.kind === 'approve' && (
        <ApproveExpenseModal
          projectId={projectId}
          expense={dialog.expense}
          currency={currency}
          limit={summary?.teamLeadLimit ?? '0'}
          onClose={close}
        />
      )}
      {dialog?.kind === 'reject' && (
        <RejectExpenseModal
          projectId={projectId}
          expense={dialog.expense}
          onClose={close}
        />
      )}
      {dialog?.kind === 'void' && (
        <VoidExpenseModal
          projectId={projectId}
          expense={dialog.expense}
          currency={currency}
          onClose={close}
        />
      )}
      {dialog?.kind === 'attach' && (
        <AttachReceiptModal
          projectId={projectId}
          expense={dialog.expense}
          onClose={close}
        />
      )}
      {dialog?.kind === 'reimburse' && (
        <ReimburseModal
          projectId={projectId}
          currency={currency}
          onClose={close}
        />
      )}
    </div>
  );
}
