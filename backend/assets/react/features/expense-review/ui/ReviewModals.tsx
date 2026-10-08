import {useState} from 'react';
import {sendExpense, useExpenseAction, type Expense} from '@/entities/expense';
import {t} from '@/shared/i18n';
import {formatMoney} from '@/shared/lib/format';
import {ConfirmModal, Field, FormModal} from '@/shared/ui';

interface ReviewProps {
  projectId: number;
  expense: Expense;
  currency: string;
  /** Above it an Admin approves too (major units). */
  limit: string;
  onClose: () => void;
}

/** The PM approves up to the Team Lead limit (above it the expense goes on to an Admin); an Admin approves anything. */
export function ApproveExpenseModal({
  projectId,
  expense,
  currency,
  limit,
  onClose,
}: ReviewProps) {
  const action = useExpenseAction(projectId);
  const aboveLimit =
    expense.status === 'SUBMITTED' && Number(expense.amount) > Number(limit);

  return (
    <ConfirmModal
      title={t('expenses.approveTitle')}
      confirmLabel={t('expenses.approve')}
      action="confirm"
      busy={action.busy}
      error={action.formError}
      onClose={onClose}
      onConfirm={() =>
        void action
          .run(() => sendExpense(`/expenses/${expense.id}/approve`, 'POST'))
          .then((done) => done && onClose())
      }
    >
      {t('expenses.approveConfirm', {
        description: expense.description,
        name: expense.paidBy.name,
        amount: formatMoney(expense.amount, currency),
      })}{' '}
      {aboveLimit &&
        t('expenses.aboveLimit', {limit: formatMoney(limit, currency)})}
    </ConfirmModal>
  );
}

export function RejectExpenseModal({
  projectId,
  expense,
  onClose,
}: Omit<ReviewProps, 'currency' | 'limit'>) {
  const action = useExpenseAction(projectId);
  const [reason, setReason] = useState('');

  return (
    <FormModal
      action="danger"
      title={t('expenses.rejectTitle', {description: expense.description})}
      submitLabel={t('expenses.reject')}
      submit={action}
      onClose={onClose}
      onSubmit={() =>
        void action
          .run(() =>
            sendExpense(`/expenses/${expense.id}/reject`, 'POST', {reason}),
          )
          .then((done) => done && onClose())
      }
    >
      <Field
        label={t('expenses.rejectReason')}
        hint={t('expenses.rejectHint')}
        className="span-2"
        error={action.errors['reason']}
      >
        <textarea
          rows={2}
          maxLength={2000}
          autoFocus
          value={reason}
          onChange={(e) => {
            action.reset();
            setReason(e.target.value);
          }}
        />
      </Field>
    </FormModal>
  );
}
