import {useState} from 'react';
import {
  PAYMENT_METHODS,
  postFinance,
  useFinanceAction,
  type Finance,
  type Movement,
} from '@/entities/finance';
import {t} from '@/shared/i18n';
import {fieldError} from '@/shared/lib/errors';
import {formatMoney, parseAmountInput, today} from '@/shared/lib/format';
import {useForm} from '@/shared/lib/forms';
import {
  ActionButton,
  AmountInput,
  Button,
  DateInput,
  Field,
  FormModal,
} from '@/shared/ui';

interface Part {
  key: number;
  /** "STAGE:11", "PETTY_CASH" or "CONTINGENCY". */
  target: string;
  categoryId: string;
  amount: string;
}

let nextKey = 1;

function newPart(target: string): Part {
  return {key: nextKey++, target, categoryId: '', amount: ''};
}

/** Money received for the project, split among its open stages, petty cash and the contingency. */
export function DepositModal({
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
  const firstTarget = openStages[0]
    ? `STAGE:${openStages[0].id}`
    : 'PETTY_CASH';
  const form = useForm({
    date: today(),
    method: 'TRANSFER',
    reference: '',
    note: '',
  });
  const [parts, setParts] = useState<Part[]>(() => [newPart(firstTarget)]);
  // Typing again clears the last refusal, so an old message never sits under a corrected field.
  const change = (key: number, patch: Partial<Part>) => {
    action.reset();
    setParts((current) =>
      current.map((p) => (p.key === key ? {...p, ...patch} : p)),
    );
  };
  const total = parts.reduce(
    (sum, p) => sum + Number(parseAmountInput(p.amount) ?? 0),
    0,
  );

  const onSubmit = async () => {
    const allocations = parts.map((p) => {
      const stage = p.target.startsWith('STAGE:');

      return {
        destination: stage ? 'STAGE' : p.target,
        stageId: stage ? Number(p.target.slice(6)) : null,
        categoryId: stage && p.categoryId ? Number(p.categoryId) : null,
        amount: parseAmountInput(p.amount) ?? p.amount,
      };
    });
    const done = await action.run(() =>
      postFinance<Movement>(`/projects/${projectId}/deposits`, {
        ...form.values,
        reference: form.values.reference || null,
        note: form.values.note || null,
        allocations,
      }),
    );
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      action="setup"
      title={t('finance.newDeposit')}
      submitLabel={t('finance.recordDeposit')}
      submit={action}
      size="wide"
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
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
        hint={t('finance.referenceHint')}
        optional
        error={action.errors['reference']}
      >
        <input maxLength={100} {...form.bind('reference')} />
      </Field>
      <fieldset className="field span-2 allocation-list">
        <legend className="field-label">{t('finance.allocations')}</legend>
        <span className="field-hint">{t('finance.allocationsHint')}</span>
        {parts.map((part, i) => {
          const path = `allocations[${i}]`;
          const stage = part.target.startsWith('STAGE:');
          const n = i + 1;

          return (
            <div className="allocation-row" key={part.key}>
              <Field
                label={t('finance.destination', {n})}
                error={fieldError(action.error, `${path}.stageId`)}
              >
                <select
                  value={part.target}
                  onChange={(e) => change(part.key, {target: e.target.value})}
                >
                  {openStages.map((s) => (
                    <option key={s.id} value={`STAGE:${s.id}`}>
                      {t('finance.stageOption', {name: s.name})}
                    </option>
                  ))}
                  <option value="PETTY_CASH">
                    {t('finance.account.PETTY_CASH')}
                  </option>
                  <option value="CONTINGENCY">
                    {t('finance.account.CONTINGENCY')}
                  </option>
                </select>
              </Field>
              {stage && finance.categories.length > 0 ? (
                <Field
                  label={t('finance.earmark', {n})}
                  optional
                  error={fieldError(action.error, `${path}.categoryId`)}
                >
                  <select
                    value={part.categoryId}
                    onChange={(e) =>
                      change(part.key, {categoryId: e.target.value})
                    }
                  >
                    <option value="">{t('finance.noEarmark')}</option>
                    {finance.categories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name}
                      </option>
                    ))}
                  </select>
                </Field>
              ) : (
                // Keeps the amount in the same column on every row.
                <span aria-hidden="true" />
              )}
              <Field
                label={t('finance.amountN', {n})}
                error={fieldError(action.error, `${path}.amount`)}
              >
                <AmountInput
                  value={part.amount}
                  onChange={(amount) => change(part.key, {amount})}
                />
              </Field>
              {parts.length > 1 && (
                <ActionButton
                  action="danger"
                  aria-label={t('finance.removeAllocation', {n})}
                  onClick={() =>
                    setParts((current) =>
                      current.filter((p) => p.key !== part.key),
                    )
                  }
                >
                  {t('finance.remove')}
                </ActionButton>
              )}
            </div>
          );
        })}
        {action.errors['allocations'] &&
          !parts.some((_, i) =>
            fieldError(action.error, `allocations[${i}]`),
          ) && (
            <span className="field-error">{action.errors['allocations']}</span>
          )}
        <div className="allocation-footer">
          <Button
            variant="ghost"
            onClick={() => setParts((c) => [...c, newPart(firstTarget)])}
          >
            {t('finance.addAllocation')}
          </Button>
          <strong>
            {t('finance.depositTotal', {
              total: formatMoney(total, finance.currency),
            })}
          </strong>
        </div>
      </fieldset>
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
