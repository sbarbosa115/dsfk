import {usePettyCashAction, type Cycle} from '@/entities/petty-cash';
import {t} from '@/shared/i18n';
import {formatMoney} from '@/shared/lib/format';
import {ConfirmModal} from '@/shared/ui';

/** An Admin says a closed cycle was reviewed. */
export function SignOffCycleModal({
  projectId,
  cycle,
  currency,
  onClose,
}: {
  projectId: number;
  cycle: Cycle;
  currency: string;
  onClose: () => void;
}) {
  const action = usePettyCashAction(projectId);

  return (
    <ConfirmModal
      title={t('pettyCash.signOffTitle', {number: cycle.number})}
      confirmLabel={t('pettyCash.signOff')}
      action="confirm"
      busy={action.busy}
      error={action.formError}
      onClose={onClose}
      onConfirm={() =>
        void action
          .post(`/petty-cash-cycles/${cycle.id}/sign-off`)
          .then((done) => done && onClose())
      }
    >
      {t('pettyCash.signOffConfirm', {
        opening: formatMoney(cycle.openingBalance, currency),
        closing: formatMoney(cycle.closingBalance, currency),
      })}
    </ConfirmModal>
  );
}
