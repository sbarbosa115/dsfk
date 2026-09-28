import {type FormEvent, useState} from 'react';
import {usePlanAction, type Plan} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {formatMoney} from '@/shared/lib/format';
import {
  ActionButton,
  Actions,
  Alert,
  ConfirmModal,
  DataTable,
  Field,
  FormModal,
  IconButton,
} from '@/shared/ui';

type Category = Plan['categories'][number];

/**
 * The project's cost categories with what each has budgeted. The PM or Admin adds them at any time, renames
 * them, and removes the ones nothing uses.
 */
export function CategoriesPanel({plan}: {plan: Plan}) {
  const add = usePlanAction();
  const [name, setName] = useState('');
  const [renaming, setRenaming] = useState<Category | null>(null);
  const [removing, setRemoving] = useState<Category | null>(null);
  const manage = plan.permissions.manageCategories;
  const totals = new Map(
    plan.budget?.byCategory.map((c) => [c.categoryId, c.total]),
  );

  const onAdd = async (event: FormEvent) => {
    event.preventDefault();
    if (
      await add.run(`/projects/${plan.project.id}/categories`, 'POST', {name})
    ) {
      setName('');
    }
  };

  return (
    <section className="card">
      <div className="card-header">
        <h2>{t('plan.categories')}</h2>
      </div>
      <p className="muted small">{t('plan.categoriesIntro')}</p>
      {manage && (
        <form className="toolbar" onSubmit={onAdd} noValidate>
          <Field label={t('plan.newCategory')} error={add.errors['name']}>
            <input value={name} onChange={(e) => setName(e.target.value)} />
          </Field>
          <ActionButton
            action="setup"
            size="md"
            type="submit"
            busy={add.busy}
            disabled={!name.trim()}
          >
            {t('plan.addCategory')}
          </ActionButton>
        </form>
      )}
      <Alert kind="error">
        {add.formError && !add.errors['name'] ? add.formError : null}
      </Alert>
      {plan.categories.length > 0 && (
        <DataTable
          columns={[
            t('plan.category'),
            ...(plan.budget ? [t('plan.budgeted')] : []),
          ]}
          actions={manage}
          rows={plan.categories}
          renderRow={(category) => (
            <tr key={category.id}>
              <td className="strong">{category.name}</td>
              {plan.budget && (
                <td className="num">
                  {formatMoney(
                    totals.get(category.id) ?? '0',
                    plan.project.currency,
                  )}
                </td>
              )}
              {manage && (
                <Actions>
                  <IconButton
                    icon="pencil"
                    label={t('plan.renameCategory')}
                    onClick={() => setRenaming(category)}
                  />
                  <ActionButton
                    action="danger"
                    onClick={() => setRemoving(category)}
                  >
                    {t('plan.remove')}
                  </ActionButton>
                </Actions>
              )}
            </tr>
          )}
        />
      )}
      {renaming && (
        <RenameModal category={renaming} onClose={() => setRenaming(null)} />
      )}
      {removing && (
        <RemoveModal category={removing} onClose={() => setRemoving(null)} />
      )}
    </section>
  );
}

function RenameModal({
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

function RemoveModal({
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
      {category.name}
    </ConfirmModal>
  );
}
