import {
  postFinance,
  useFinanceAction,
  type Finance,
  type Movement,
} from '@/entities/finance';
import {t} from '@/shared/i18n';
import {formatMoney, parseAmountInput, today} from '@/shared/lib/format';
import {useForm} from '@/shared/lib/forms';
import {DateInput, Field, FormModal, MoneyField} from '@/shared/ui';

/** The Admin moves contingency money into a stage that ran short, saying why. */
export function DrawModal({
  projectId,
  finance,
  onClose,
}: {
  projectId: number;
  finance: Finance;
  onClose: () => void;
}) {
  const action = useFinanceAction(projectId);
  const openStages = finance.stages.filter((s) => s.status !== 'COMPLETED');
  const form = useForm({
    stageId: String(openStages[0]?.id ?? ''),
    amount: '',
    date: today(),
    reason: '',
  });

  const onSubmit = async () => {
    const done = await action.run(() =>
      postFinance<Movement>(`/projects/${projectId}/contingency/draws`, {
        stageId: form.values.stageId ? Number(form.values.stageId) : null,
        amount: parseAmountInput(form.values.amount) ?? form.values.amount,
        date: form.values.date,
        reason: form.values.reason,
      }),
    );
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      action="revert"
      title={t('finance.draw')}
      submitLabel={t('finance.drawSubmit')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field label={t('finance.stage')} error={action.errors['stageId']}>
        <select {...form.bind('stageId')}>
          {openStages.map((s) => (
            <option key={s.id} value={s.id}>
              {s.name}
            </option>
          ))}
        </select>
      </Field>
      <MoneyField
        label={t('finance.amount')}
        currency={finance.currency}
        hint={t('finance.contingencyAvailable', {
          amount: formatMoney(finance.contingency.balance, finance.currency),
        })}
        error={action.errors['amount']}
        value={form.values.amount}
        onChange={(text) => form.set('amount', text)}
      />
      <Field label={t('finance.date')} error={action.errors['date']}>
        <DateInput {...form.bind('date')} />
      </Field>
      <Field
        label={t('finance.drawReason')}
        className="span-2"
        error={action.errors['reason']}
      >
        <textarea rows={2} maxLength={2000} {...form.bind('reason')} />
      </Field>
    </FormModal>
  );
}
