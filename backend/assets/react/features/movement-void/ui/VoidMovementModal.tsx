import {useState} from 'react';
import {
  destinations,
  postFinance,
  useFinanceAction,
  type Movement,
} from '@/entities/finance';
import {t} from '@/shared/i18n';
import {formatDate, formatMoney} from '@/shared/lib/format';
import {Alert, Field, FormModal} from '@/shared/ui';

/** Voids a deposit or draw recorded by mistake: it stays listed, out of the balances. */
export function VoidMovementModal({
  projectId,
  movement,
  currency,
  onClose,
}: {
  projectId: number;
  movement: Movement;
  currency: string;
  onClose: () => void;
}) {
  const action = useFinanceAction(projectId);
  const [reason, setReason] = useState('');

  const onSubmit = async () => {
    const done = await action.run(() =>
      postFinance<Movement>(`/movements/${movement.id}/void`, {reason}),
    );
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      action="danger"
      title={t('finance.voidTitle')}
      submitLabel={t('finance.void')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <div className="span-2">
        <Alert kind="warning">
          {t('finance.voidConfirm', {
            type: t(`finance.type.${movement.type}`),
            date: formatDate(movement.date),
            amount: formatMoney(movement.amount, currency, {exact: true}),
            destinations: destinations(movement),
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
