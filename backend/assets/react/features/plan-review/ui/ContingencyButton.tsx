import {useState} from 'react';
import {usePlanAction, type Plan} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {formatAmountForInput, parseAmountInput} from '@/shared/lib/format';
import {FormModal, IconButton, MoneyField} from '@/shared/ui';

/** Edit the contingency reserve while the budget is a draft. */
export function ContingencyButton({plan}: {plan: Plan}) {
  const [open, setOpen] = useState(false);
  const action = usePlanAction();
  const [amount, setAmount] = useState(
    formatAmountForInput(plan.budget?.contingency),
  );

  if (!plan.permissions.edit) {
    return null;
  }

  return (
    <>
      <IconButton
        icon="pencil"
        label={t('plan.editContingency')}
        onClick={() => setOpen(true)}
      />
      {open && (
        <FormModal
          title={t('plan.editContingency')}
          submit={action}
          onClose={() => setOpen(false)}
          onSubmit={async () => {
            const contingency = parseAmountInput(amount) ?? amount;
            if (
              await action.run(
                `/projects/${plan.project.id}/budget/contingency`,
                'PUT',
                {contingency},
              )
            ) {
              setOpen(false);
            }
          }}
        >
          <MoneyField
            label={t('plan.contingency')}
            currency={plan.project.currency}
            hint={t('plan.contingencyHint')}
            error={action.errors['contingency']}
            value={amount}
            onChange={setAmount}
          />
        </FormModal>
      )}
    </>
  );
}
