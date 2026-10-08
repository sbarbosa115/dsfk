import {useState} from 'react';
import {sendExpense, useExpenseAction, type Expense} from '@/entities/expense';
import {t} from '@/shared/i18n';
import {formatMoney} from '@/shared/lib/format';
import {Alert, Field, FormModal} from '@/shared/ui';

/** An Admin voids an approved expense recorded by mistake; its money goes back where it came from. */
export function VoidExpenseModal({
  projectId,
  expense,
  currency,
  onClose,
}: {
  projectId: number;
  expense: Expense;
  currency: string;
  onClose: () => void;
}) {
  const action = useExpenseAction(projectId);
  const [reason, setReason] = useState('');

  return (
    <FormModal
      action="danger"
      title={t('expenses.voidTitle')}
      submitLabel={t('finance.void')}
      submit={action}
      onClose={onClose}
      onSubmit={() =>
        void action
          .run(() =>
            sendExpense(`/expenses/${expense.id}/void`, 'POST', {reason}),
          )
          .then((done) => done && onClose())
      }
    >
      <div className="span-2">
        <Alert kind="warning">
          {t('expenses.voidConfirm', {
            description: expense.description,
            amount: formatMoney(expense.amount, currency, {exact: true}),
          })}
        </Alert>
      </div>
      <Field
        label={t('finance.voidReason')}
        className="span-2"
        error={action.errors['reason']}
      >
        <textarea
          rows={2}
          maxLength={2000}
          autoFocus
          value={reason}
          onChange={(e) => setReason(e.target.value)}
        />
      </Field>
    </FormModal>
  );
}
