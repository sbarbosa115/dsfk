import {useQuery} from '@tanstack/react-query';
import {
  expenseKey,
  expenseStatusLabel,
  expenseTone,
  fetchExpense,
  paidFromLabel,
  type ExpenseEvent,
} from '@/entities/expense';
import {attachmentUrl} from '@/entities/finance';
import {t} from '@/shared/i18n';
import {formatDate, formatDateTime, formatMoney} from '@/shared/lib/format';
import {
  actionClass,
  Badge,
  DefinitionList,
  ErrorState,
  Loading,
  Modal,
} from '@/shared/ui';

/** An expense in full: its details, receipts and history (who did what, and what it said before a correction). */
export function ExpenseDetailModal({
  expenseId,
  currency,
  onClose,
}: {
  expenseId: number;
  currency: string;
  onClose: () => void;
}) {
  const expense = useQuery({
    queryKey: expenseKey(expenseId),
    queryFn: () => fetchExpense(expenseId),
  });
  const money = (amount: string) =>
    formatMoney(amount, currency, {exact: true});
  const e = expense.data;

  return (
    <Modal
      title={e?.description ?? t('expenses.view')}
      onClose={onClose}
      size="wide"
    >
      {expense.error ? (
        <ErrorState error={expense.error} />
      ) : !e ? (
        <Loading />
      ) : (
        <>
          <DefinitionList
            items={[
              [
                t('common.status'),
                <Badge key="status" value={expenseTone(e.status)}>
                  {expenseStatusLabel(e.status)}
                </Badge>,
              ],
              [t('finance.amount'), money(e.amount)],
              [t('finance.date'), formatDate(e.date)],
              [t('finance.stage'), e.stage.name],
              [t('plan.category'), e.category.name],
              [t('expenses.paid'), paidFromLabel(e)],
              e.supplier && [t('expenses.supplier'), e.supplier],
              e.invoiceNumber && [t('expenses.invoiceNumber'), e.invoiceNumber],
              e.rejectionReason && [
                t('expenses.rejectReason'),
                e.rejectionReason,
              ],
              e.reimbursement && [
                t('expenses.reimbursed'),
                t('expenses.reimbursedOn', {
                  date: formatDate(e.reimbursement.date),
                  method: t(`finance.methods.${e.reimbursement.method}`),
                }),
              ],
            ]}
          />
          <h3>{t('expenses.receipts')}</h3>
          {e.attachments.length === 0 ? (
            <p className="muted small">{t('expenses.noReceipt')}</p>
          ) : (
            <div className="row-actions">
              {e.attachments.map((a) => (
                <a
                  key={a.id}
                  className={actionClass('file')}
                  href={attachmentUrl(a.id)}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {a.name}
                </a>
              ))}
            </div>
          )}
          <h3>{t('expenses.history')}</h3>
          <ul className="history-list">
            {(e.events ?? []).map((ev, i) => (
              <li key={i}>
                <strong>{t(`expenses.event.${ev.type}`)}</strong> · {ev.user} ·{' '}
                {formatDateTime(ev.createdAt)}
                {ev.comment && <div>“{ev.comment}”</div>}
                {ev.previous && (
                  <div className="small muted">{previous(ev, money)}</div>
                )}
              </li>
            ))}
          </ul>
        </>
      )}
    </Modal>
  );
}

function previous(
  event: ExpenseEvent,
  money: (amount: string) => string,
): string {
  const p = event.previous!;

  return t('expenses.before', {
    description: p.description,
    amount: money(p.amount),
    date: formatDate(p.date),
    stage: p.stage,
    category: p.category,
  });
}
