import {usePlanAction, type Plan, type PlanStage} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {emptyToNull, useForm} from '@/shared/lib/forms';
import {DateInput, Field, FormModal} from '@/shared/ui';

/** Adds a stage (stage = undefined) or edits one. After approval only the name changes. */
export function StageFormModal({
  plan,
  stage,
  onClose,
}: {
  plan: Plan;
  stage?: PlanStage;
  onClose: () => void;
}) {
  const action = usePlanAction();
  const form = useForm({
    name: stage?.name ?? '',
    plannedStart: stage?.plannedStart ?? '',
    plannedEnd: stage?.plannedEnd ?? '',
  });
  const datesLocked = !plan.permissions.edit;

  const onSubmit = async () => {
    const {name, plannedStart, plannedEnd} = form.values;
    const body = datesLocked
      ? {name}
      : {name, ...emptyToNull({plannedStart, plannedEnd})};
    const done = stage
      ? await action.run(`/stages/${stage.id}`, 'PATCH', body)
      : await action.run(`/projects/${plan.project.id}/stages`, 'POST', body);
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      action={stage ? 'confirm' : 'setup'}
      title={stage ? t('plan.editStage') : t('plan.addStage')}
      submitLabel={stage ? t('common.save') : t('plan.addStage')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field
        label={t('plan.stageName')}
        error={action.errors['name']}
        className="span-2"
      >
        <input autoFocus {...form.bind('name')} />
      </Field>
      <Field
        label={t('projects.plannedStart')}
        optional
        hint={datesLocked ? t('plan.datesLocked') : undefined}
        error={action.errors['plannedStart']}
      >
        <DateInput disabled={datesLocked} {...form.bind('plannedStart')} />
      </Field>
      <Field
        label={t('projects.plannedEnd')}
        optional
        error={action.errors['plannedEnd']}
      >
        <DateInput disabled={datesLocked} {...form.bind('plannedEnd')} />
      </Field>
    </FormModal>
  );
}
