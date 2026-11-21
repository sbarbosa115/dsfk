import {useState} from 'react';
import {usePlanAction, type Plan} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {ConfirmModal, Field, FormModal} from '@/shared/ui';

export type Category = Plan['categories'][number];

/** "Nueva categoría": the PM or Admin adds one at any time, even after the budget is approved. */
export function AddCategoryModal({
  plan,
  onClose,
}: {
  plan: Plan;
  onClose: () => void;
}) {
  const action = usePlanAction();
  const [name, setName] = useState('');

  return (
    <FormModal
      title={t('categories.add')}
      submitLabel={t('plan.addCategory')}
      action="setup"
      submit={action}
      onClose={onClose}
      onSubmit={async () => {
        if (
          await action.run(`/projects/${plan.project.id}/categories`, 'POST', {
            name,
          })
        ) {
          onClose();
        }
      }}
    >
      <Field
        label={t('plan.category')}
        error={action.errors['name']}
        className="span-2"
      >
        <input
          autoFocus
          value={name}
          onChange={(e) => {
            setName(e.target.value);
            action.reset();
          }}
        />
      </Field>
    </FormModal>
  );
}

export function RenameCategoryModal({
  category,
  onClose,
}: {
  category: Category;
  onClose: () => void;
}) {
  const action = usePlanAction();
  const [name, setName] = useState(category.name);

  return (
    <FormModal
      title={t('plan.renameCategory')}
      submit={action}
      onClose={onClose}
      onSubmit={async () => {
        if (await action.run(`/categories/${category.id}`, 'PATCH', {name})) {
          onClose();
        }
      }}
    >
      <Field
        label={t('plan.category')}
        error={action.errors['name']}
        className="span-2"
      >
        <input
          autoFocus
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
      </Field>
    </FormModal>
  );
}

export function RemoveCategoryModal({
  category,
  onClose,
}: {
  category: Category;
  onClose: () => void;
}) {
  const action = usePlanAction();

  return (
    <ConfirmModal
      title={t('plan.remove')}
      confirmLabel={t('plan.remove')}
      busy={action.busy}
      error={action.formError}
      onConfirm={async () => {
        if (await action.run(`/categories/${category.id}`, 'DELETE')) {
          onClose();
        }
      }}
      onClose={onClose}
    >
      {t('plan.removeCategoryConfirm', {name: category.name})}
    </ConfirmModal>
  );
}
