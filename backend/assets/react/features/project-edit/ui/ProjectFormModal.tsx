import {useQuery, useQueryClient} from '@tanstack/react-query';
import {
  createProject,
  PROJECT_STATUSES,
  PROJECTS_KEY,
  projectKey,
  projectStatusLabel,
  updateProject,
  type Project,
  type ProjectStatus,
} from '@/entities/project';
import {fetchSettings, SETTINGS_KEY} from '@/entities/settings';
import {t} from '@/shared/i18n';
import {emptyToNull, useForm, useSubmit} from '@/shared/lib/forms';
import {DateInput, Field, FormModal} from '@/shared/ui';

/** Admin only: creates a project (project = null) or edits one. The currency is chosen once. */
export function ProjectFormModal({
  project,
  onClose,
  onSaved,
}: {
  project: Project | null;
  onClose: () => void;
  onSaved?: (project: Project) => void;
}) {
  const queryClient = useQueryClient();
  const settings = useQuery({
    queryKey: SETTINGS_KEY,
    queryFn: fetchSettings,
    enabled: !project,
  });
  const form = useForm({
    name: project?.name ?? '',
    description: project?.description ?? '',
    currency: project?.currency ?? '',
    status: (project?.status ?? 'DRAFT') as ProjectStatus,
    plannedStart: project?.plannedStart ?? '',
    plannedEnd: project?.plannedEnd ?? '',
  });
  const submit = useSubmit();
  const currency = form.values.currency || settings.data?.defaultCurrency || '';

  const onSubmit = async () => {
    const {name, description, status, plannedStart, plannedEnd} = form.values;
    const body = {
      name,
      description,
      status,
      ...emptyToNull({plannedStart, plannedEnd}),
    };
    const result = await submit.run(() =>
      project
        ? updateProject(project.id, body)
        : createProject({...body, currency: currency.toUpperCase()}),
    );
    if (result.ok) {
      void queryClient.invalidateQueries({queryKey: PROJECTS_KEY});
      queryClient.setQueryData(projectKey(result.value.id), result.value);
      onSaved?.(result.value);
      onClose();
    }
  };

  return (
    <FormModal
      action={project ? 'confirm' : 'setup'}
      title={project ? t('projects.edit') : t('projects.new')}
      submitLabel={project ? t('common.save') : t('common.create')}
      onClose={onClose}
      onSubmit={onSubmit}
      submit={submit}
    >
      <Field
        label={t('projects.name')}
        error={submit.errors['name']}
        className="span-2"
      >
        <input autoFocus {...form.bind('name')} />
      </Field>
      <Field
        label={t('projects.description')}
        optional
        error={submit.errors['description']}
        className="span-2"
      >
        <textarea rows={3} {...form.bind('description')} />
      </Field>
      <Field
        label={t('projects.currency')}
        hint={t('projects.currencyHint')}
        error={submit.errors['currency']}
      >
        <input
          maxLength={3}
          className="uppercase"
          disabled={!!project}
          value={currency}
          onChange={(event) => form.set('currency', event.target.value)}
        />
      </Field>
      <Field
        label={t('projects.statusLabel')}
        hint={t(`projects.statusHint.${form.values.status}`)}
        error={submit.errors['status']}
      >
        <select {...form.bind('status')}>
          {PROJECT_STATUSES.map((status) => (
            <option key={status} value={status}>
              {projectStatusLabel(status)}
            </option>
          ))}
        </select>
      </Field>
      <Field
        label={t('projects.plannedStart')}
        optional
        error={submit.errors['plannedStart']}
      >
        <DateInput {...form.bind('plannedStart')} />
      </Field>
      <Field
        label={t('projects.plannedEnd')}
        optional
        error={submit.errors['plannedEnd']}
      >
        <DateInput {...form.bind('plannedEnd')} />
      </Field>
    </FormModal>
  );
}
