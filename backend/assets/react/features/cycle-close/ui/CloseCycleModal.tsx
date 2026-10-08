import {useState} from 'react';
import {usePettyCashAction, type PettyCash} from '@/entities/petty-cash';
import {t} from '@/shared/i18n';
import {formatMoney} from '@/shared/lib/format';
import {Alert, Field, FormModal} from '@/shared/ui';

/** The PM (or an Admin) closes the current cycle, usually when the money runs out; an Admin then signs it off. */
export function CloseCycleModal({
  projectId,
  pettyCash,
  onClose,
}: {
  projectId: number;
  pettyCash: PettyCash;
  onClose: () => void;
}) {
  const action = usePettyCashAction(projectId);
  const [note, setNote] = useState('');

  return (
    <FormModal
      title={t('pettyCash.closeTitle', {number: pettyCash.current.number})}
      submitLabel={t('pettyCash.close')}
      submit={action}
      onClose={onClose}
      onSubmit={() =>
        void action
          .post(`/projects/${projectId}/petty-cash/close`, {note: note || null})
          .then((done) => done && onClose())
      }
    >
      <div className="span-2">
        <Alert kind="info">
          {t('pettyCash.closeConfirm', {
            balance: formatMoney(pettyCash.balance, pettyCash.currency, {
              exact: true,
            }),
          })}
        </Alert>
      </div>
      <Field
        label={t('pettyCash.closingNote')}
        optional
        className="span-2"
        error={action.errors['note']}
      >
        <textarea
          rows={2}
          maxLength={2000}
          value={note}
          onChange={(e) => setNote(e.target.value)}
        />
      </Field>
    </FormModal>
  );
}
