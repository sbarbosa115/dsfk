import {
  usePlanAction,
  type PlanMilestone,
  type PlanStage,
} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {formatPercent} from '@/shared/lib/format';
import {emptyToNull, useForm} from '@/shared/lib/forms';
import {DateInput, Field, FormModal} from '@/shared/ui';

/** Adds a milestone (milestone = undefined) or edits one. Suggests the weight still missing to reach 100 %. */
export function MilestoneFormModal({
  stage,
  milestone,
  onClose,
}: {
  stage: PlanStage;
  milestone?: PlanMilestone;
  onClose: () => void;
}) {
  const action = usePlanAction();
  const remaining =
    10000 - stage.milestoneWeightTotal + (milestone?.weight ?? 0);
  const form = useForm({
    name: milestone?.name ?? '',
    weight: milestone
      ? String(milestone.weight / 100).replace('.', ',')
      : remaining > 0
        ? String(remaining / 100).replace('.', ',')
        : '',
    plannedDate: milestone?.plannedDate ?? '',
  });

  const onSubmit = async () => {
    const body = {
      name: form.values.name,
      weight: form.values.weight.trim().replace(',', '.'),
      ...emptyToNull({plannedDate: form.values.plannedDate}),
    };
    const done = milestone
      ? await action.run(`/milestones/${milestone.id}`, 'PATCH', body)
      : await action.run(`/stages/${stage.id}/milestones`, 'POST', body);
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      action={milestone ? 'confirm' : 'setup'}
      title={
        milestone
          ? t('plan.editMilestone')
          : `${t('plan.addMilestone')} · ${stage.name}`
      }
      submitLabel={milestone ? t('common.save') : t('plan.addMilestone')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field
        label={t('plan.milestoneName')}
        error={action.errors['name']}
        className="span-2"
      >
        <input autoFocus {...form.bind('name')} />
      </Field>
      <Field
        label={t('plan.weight')}
        hint={t('plan.weightHint', {
          remaining: formatPercent(Math.max(0, remaining)),
        })}
        error={action.errors['weight']}
      >
        <input inputMode="decimal" {...form.bind('weight')} />
      </Field>
      <Field
        label={t('plan.plannedDate')}
        optional
        error={action.errors['plannedDate']}
      >
        <DateInput {...form.bind('plannedDate')} />
      </Field>
    </FormModal>
  );
}
