import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {
  expensesKey,
  expensesPath,
  sendExpense,
  useExpenseAction,
  type Expense,
  type ExpensePage,
} from '@/entities/expense';
import {PAYMENT_METHODS} from '@/entities/finance';
import {api} from '@/shared/api';
import {t} from '@/shared/i18n';
import {formatDate, formatMoney, today} from '@/shared/lib/format';
import {useForm} from '@/shared/lib/forms';
import {
  Checkbox,
  DateInput,
  EmptyState,
  Field,
  FieldGroup,
  FormModal,
  Loading,
} from '@/shared/ui';

/** The PM or an Admin pays approved Team Lead expenses back from petty cash, in one movement. */
export function ReimburseModal({
  projectId,
  currency,
  onClose,
}: {
  projectId: number;
  currency: string;
  onClose: () => void;
}) {
  const action = useExpenseAction(projectId);
  const pending = useQuery({
    queryKey: [...expensesKey(projectId), 'to-reimburse'],
    queryFn: () =>
      api<ExpensePage>(
        `${expensesPath(projectId)}?status=APPROVED&perPage=100`,
      ),
  });
  const [chosen, setChosen] = useState<number[]>([]);
  const form = useForm({date: today(), method: 'TRANSFER', reference: ''});
  const due = (pending.data?.items ?? []).filter(
    (e) => e.permissions.reimburse,
  );
  const total = due
    .filter((e) => chosen.includes(e.id))
    .reduce((sum, e) => sum + Number(e.amount), 0);
  const toggle = (expense: Expense, on: boolean) => {
    action.reset();
    setChosen((c) =>
      on ? [...c, expense.id] : c.filter((id) => id !== expense.id),
    );
  };

  return (
    <FormModal
      title={t('expenses.reimburse')}
      submitLabel={t('expenses.reimburseSubmit', {
        total: formatMoney(total, currency, {exact: true}),
      })}
      submit={action}
      size="wide"
      onClose={onClose}
      onSubmit={() =>
        void action
          .run(() =>
            sendExpense<Expense[]>(
              `/projects/${projectId}/reimbursements`,
              'POST',
              {
                expenseIds: chosen,
                date: form.values.date,
                method: form.values.method,
                reference: form.values.reference || null,
              },
            ),
          )
          .then((done) => done && onClose())
      }
    >
      <div className="span-2">
        {!pending.data ? (
          <Loading />
        ) : due.length === 0 ? (
          <EmptyState>{t('expenses.nothingToReimburse')}</EmptyState>
        ) : (
          <FieldGroup
            label={t('expenses.toReimburse')}
            hint={t('expenses.reimburseHint')}
            error={action.errors['expenseIds']}
          >
            {due.map((e) => (
              <Checkbox
                key={e.id}
                checked={chosen.includes(e.id)}
                onChange={(on) => toggle(e, on)}
                label={t('expenses.reimburseLine', {
                  name: e.paidBy.name,
                  description: e.description,
                  date: formatDate(e.date),
                  amount: formatMoney(e.amount, currency, {exact: true}),
                })}
              />
            ))}
          </FieldGroup>
        )}
      </div>
      <Field label={t('finance.date')} error={action.errors['date']}>
        <DateInput {...form.bind('date')} />
      </Field>
      <Field label={t('finance.method')} error={action.errors['method']}>
        <select {...form.bind('method')}>
          {PAYMENT_METHODS.map((m) => (
            <option key={m} value={m}>
              {t(`finance.methods.${m}`)}
            </option>
          ))}
        </select>
      </Field>
      <Field
        label={t('finance.reference')}
        optional
        error={action.errors['reference']}
      >
        <input maxLength={100} {...form.bind('reference')} />
      </Field>
    </FormModal>
  );
}
