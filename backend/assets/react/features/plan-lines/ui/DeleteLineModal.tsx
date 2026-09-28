import {usePlanAction, type PlanLine} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {ConfirmModal} from '@/shared/ui';

export function DeleteLineModal({
  line,
  onClose,
}: {
  line: PlanLine;
  onClose: () => void;
}) {
  const action = usePlanAction();

  return (
    <ConfirmModal
      title={t('plan.deleteLine')}
      confirmLabel={t('plan.deleteLine')}
      busy={action.busy}
      error={action.formError}
      onConfirm={async () => {
        if (await action.run(`/budget-lines/${line.id}`, 'DELETE')) {
          onClose();
        }
      }}
      onClose={onClose}
    >
      {t('plan.deleteLineConfirm', {name: line.description})}
    </ConfirmModal>
  );
}
