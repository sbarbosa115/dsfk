import {
  sendExpense,
  useExpenseAction,
  type Expense,
  type ExpenseBody,
} from '@/entities/expense';
import type {Plan} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {
  formatAmountForInput,
  parseAmountInput,
  today,
} from '@/shared/lib/format';
import {useForm} from '@/shared/lib/forms';
import {DateInput, Field, FormModal, MoneyField} from '@/shared/ui';

/**
 * Records an expense (expense = undefined) or lets its Team Lead correct a pending or rejected one. The PM and
 * Admins (`manager`) say where the money comes from; a Team Lead paid out of pocket.
 */
export function ExpenseFormModal({
  plan,
  manager,
  expense,
  onClose,
}: {
  plan: Plan;
  manager: boolean;
  expense?: Expense;
  onClose: () => void;
}) {
  const projectId = plan.project.id;
  const action = useExpenseAction(projectId);
  const openStages = plan.stages.filter(
    (s) => s.status !== 'COMPLETED' || s.id === expense?.stage.id,
  );
  const form = useForm({
    stageId: String(expense?.stage.id ?? openStages[0]?.id ?? ''),
    categoryId: String(expense?.category.id ?? plan.categories[0]?.id ?? ''),
    date: expense?.date ?? today(),
    amount: formatAmountForInput(expense?.amount),
    description: expense?.description ?? '',
    supplier: expense?.supplier ?? '',
    invoiceNumber: expense?.invoiceNumber ?? '',
    paidFrom: manager ? 'STAGE' : 'OUT_OF_POCKET',
  });
  const bind = (name: Parameters<typeof form.bind>[0]) => {
    const b = form.bind(name);
    return {
      ...b,
      onChange: (e: Parameters<typeof b.onChange>[0]) => {
        action.reset();
        b.onChange(e);
      },
    };
  };

  const onSubmit = async () => {
    const v = form.values;
    const body: ExpenseBody = {
      stageId: v.stageId ? Number(v.stageId) : null,
      categoryId: v.categoryId ? Number(v.categoryId) : null,
      date: v.date,
      amount: parseAmountInput(v.amount) ?? v.amount,
      description: v.description,
      supplier: v.supplier || null,
      invoiceNumber: v.invoiceNumber || null,
      ...(expense ? {} : {paidFrom: v.paidFrom as ExpenseBody['paidFrom']}),
    };
    const done = await action.run(() =>
      expense
        ? sendExpense(`/expenses/${expense.id}`, 'PUT', body)
        : sendExpense(`/projects/${projectId}/expenses`, 'POST', body),
    );
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      action={expense ? 'confirm' : 'setup'}
      title={expense ? t('expenses.correct') : t('expenses.record')}
      submitLabel={
        expense ? t('expenses.sendAgain') : t('expenses.recordSubmit')
      }
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field
        label={t('expenses.description')}
        className="span-2"
        error={action.errors['description']}
      >
        <input autoFocus maxLength={255} {...bind('description')} />
      </Field>
      <Field label={t('finance.stage')} error={action.errors['stageId']}>
        <select {...bind('stageId')}>
          {openStages.map((s) => (
            <option key={s.id} value={s.id}>
              {s.name}
            </option>
          ))}
        </select>
      </Field>
      <Field label={t('plan.category')} error={action.errors['categoryId']}>
        <select {...bind('categoryId')}>
          {plan.categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
      </Field>
      <Field label={t('finance.date')} error={action.errors['date']}>
        <DateInput {...bind('date')} />
      </Field>
      <MoneyField
        label={t('finance.amount')}
        currency={plan.project.currency}
        error={action.errors['amount']}
        value={form.values.amount}
        onChange={(text) => {
          action.reset();
          form.set('amount', text);
        }}
      />
      {manager && !expense && (
        <Field
          label={t('expenses.paidFromLabel')}
          className="span-2"
          error={action.errors['paidFrom']}
        >
          <select {...bind('paidFrom')}>
            <option value="STAGE">{t('expenses.paidFrom.STAGE')}</option>
            <option value="PETTY_CASH">
              {t('expenses.paidFrom.PETTY_CASH')}
            </option>
          </select>
        </Field>
      )}
      <Field
        label={t('expenses.supplier')}
        optional
        error={action.errors['supplier']}
      >
        <input maxLength={150} {...bind('supplier')} />
      </Field>
      <Field
        label={t('expenses.invoiceNumber')}
        optional
        error={action.errors['invoiceNumber']}
      >
        <input maxLength={60} {...bind('invoiceNumber')} />
      </Field>
    </FormModal>
  );
}
