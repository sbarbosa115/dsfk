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

/**
 * The Admin closes a stage whose milestones are met. What is left of its money goes to the project fund, or to
 * another open stage they choose: stages can run at the same time, so none is "the next one".
 */
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
  const form = useForm({actualEnd: today(), toStageId: ''});
  const left = Number(stage.available) > 0;
  const amount = formatMoney(stage.available, finance.currency, {exact: true});
  const otherOpen = finance.stages.filter(
    (s) => s.id !== stage.id && s.status !== 'COMPLETED',
  );
  const target = otherOpen.find((s) => String(s.id) === form.values.toStageId);

  const onSubmit = async () => {
    const done = await action.run(() =>
      postFinance<Finance>(`/stages/${stage.id}/complete`, {
        actualEnd: form.values.actualEnd,
        toStageId: target?.id ?? null,
      }),
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
          {left
            ? target
              ? t('finance.carryToStage', {amount, stage: target.name})
              : t('finance.carryToFund', {amount})
            : t('finance.nothingToCarry')}{' '}
          {t('finance.completeFinal')}
        </Alert>
      </div>
      {left && (
        <Field
          label={t('finance.carryTarget')}
          error={action.errors['toStageId']}
        >
          <select {...form.bind('toStageId')}>
            <option value="">{t('finance.account.PROJECT_FUND')}</option>
            {otherOpen.map((s) => (
              <option key={s.id} value={s.id}>
                {t('finance.stageOption', {name: s.name})}
              </option>
            ))}
          </select>
        </Field>
      )}
      <Field label={t('finance.actualEnd')} error={action.errors['actualEnd']}>
        <DateInput {...form.bind('actualEnd')} />
      </Field>
    </FormModal>
  );
}
