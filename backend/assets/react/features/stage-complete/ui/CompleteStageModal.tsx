import {
  postFinance,
  useFinanceAction,
  type Finance,
  type StageFunding,
} from '@/entities/finance';
import {t} from '@/shared/i18n';
import {formatMoney, today} from '@/shared/lib/format';
import {useForm} from '@/shared/lib/forms';
import {Alert, DateInput, Field, FormModal} from '@/shared/ui';

/** The Admin closes a stage whose milestones are met; what is left of its money moves on. */
export function CompleteStageModal({
  projectId,
  finance,
  stage,
  onClose,
}: {
  projectId: number;
  finance: Finance;
  stage: StageFunding;
  onClose: () => void;
}) {
  const action = useFinanceAction(projectId);
  const form = useForm({actualEnd: today()});
  const left = Number(stage.available);
  const amount = formatMoney(stage.available, finance.currency, {exact: true});

  const onSubmit = async () => {
    const done = await action.run(() =>
      postFinance<Finance>(`/stages/${stage.id}/complete`, form.values),
    );
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      title={t('finance.completeTitle', {name: stage.name})}
      submitLabel={t('finance.completeStage')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <div className="span-2">
        <Alert kind="info">
          {left > 0
            ? stage.nextStage
              ? t('finance.carryToStage', {amount, stage: stage.nextStage})
              : t('finance.carryToContingency', {amount})
            : t('finance.nothingToCarry')}{' '}
          {t('finance.completeFinal')}
        </Alert>
      </div>
      <Field label={t('finance.actualEnd')} error={action.errors['actualEnd']}>
        <DateInput {...form.bind('actualEnd')} />
      </Field>
    </FormModal>
  );
}
