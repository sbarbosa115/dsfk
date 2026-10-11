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

/** The Admin gives project fund money to an open stage or to the caja menor. */
export function AssignFundModal({
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
    /** "STAGE:11" or "PETTY_CASH". */
    target: openStages[0] ? `STAGE:${openStages[0].id}` : 'PETTY_CASH',
    amount: '',
    date: today(),
    note: '',
  });

  const onSubmit = async () => {
    const stage = form.values.target.startsWith('STAGE:');
    const done = await action.run(() =>
      postFinance<Movement>(`/projects/${projectId}/fund/assignments`, {
        destination: stage ? 'STAGE' : form.values.target,
        stageId: stage ? Number(form.values.target.slice(6)) : null,
        amount: parseAmountInput(form.values.amount) ?? form.values.amount,
        date: form.values.date,
        note: form.values.note || null,
      }),
    );
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      action="setup"
      title={t('finance.assignFund')}
      submitLabel={t('finance.assignSubmit')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field
        label={t('finance.assignDestination')}
        error={action.errors['stageId'] ?? action.errors['destination']}
      >
        <select {...form.bind('target')}>
          {openStages.map((s) => (
            <option key={s.id} value={`STAGE:${s.id}`}>
              {t('finance.stageOption', {name: s.name})}
            </option>
          ))}
          <option value="PETTY_CASH">{t('finance.account.PETTY_CASH')}</option>
        </select>
      </Field>
      <MoneyField
        label={t('finance.amount')}
        currency={finance.currency}
        hint={t('finance.fundAvailable', {
          amount: formatMoney(finance.projectFund.balance, finance.currency, {
            exact: true,
          }),
        })}
        error={action.errors['amount']}
        value={form.values.amount}
        onChange={(text) => form.set('amount', text)}
      />
      <Field label={t('finance.date')} error={action.errors['date']}>
        <DateInput {...form.bind('date')} />
      </Field>
      <Field
        label={t('finance.note')}
        optional
        className="span-2"
        error={action.errors['note']}
      >
        <textarea rows={2} maxLength={2000} {...form.bind('note')} />
      </Field>
    </FormModal>
  );
}
