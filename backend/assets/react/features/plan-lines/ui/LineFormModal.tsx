import {
  usePlanAction,
  type Plan,
  type PlanLine,
  type PlanStage,
} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {
  formatAmountForInput,
  formatMoney,
  parseAmountInput,
} from '@/shared/lib/format';
import {useForm} from '@/shared/lib/forms';
import {Field, FormModal, MoneyField} from '@/shared/ui';

/** "12,5" → "12.5": quantities are typed with the Colombian decimal comma. */
function quantity(text: string): string {
  return text.trim().replace(/\./g, '').replace(',', '.');
}

/** Adds a budget line to a stage (line = undefined) or edits one. Shows the total it works out to. */
export function LineFormModal({
  plan,
  stage,
  line,
  onClose,
}: {
  plan: Plan;
  stage: PlanStage;
  line?: PlanLine;
  onClose: () => void;
}) {
  const action = usePlanAction();
  const form = useForm({
    categoryId: String(line?.categoryId ?? plan.categories[0]?.id ?? ''),
    description: line?.description ?? '',
    unit: line?.unit ?? '',
    quantity: line ? line.quantity.replace('.', ',') : '',
    unitPrice: formatAmountForInput(line?.unitPrice),
  });
  const price = parseAmountInput(form.values.unitPrice);
  const qty = Number(quantity(form.values.quantity));
  const preview =
    price && Number.isFinite(qty) && qty > 0
      ? formatMoney(Number(price) * qty, plan.project.currency, {exact: true})
      : null;

  const onSubmit = async () => {
    const body = {
      categoryId: Number(form.values.categoryId),
      description: form.values.description,
      unit: form.values.unit,
      quantity: quantity(form.values.quantity),
      unitPrice: price ?? form.values.unitPrice,
    };
    const done = line
      ? await action.run(`/budget-lines/${line.id}`, 'PUT', body)
      : await action.run(`/stages/${stage.id}/lines`, 'POST', body);
    if (done) {
      onClose();
    }
  };

  return (
    <FormModal
      title={line ? t('plan.editLine') : `${t('plan.addLine')} · ${stage.name}`}
      submitLabel={line ? t('common.save') : t('plan.addLine')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field label={t('plan.category')} error={action.errors['categoryId']}>
        <select {...form.bind('categoryId')}>
          {plan.categories.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </select>
      </Field>
      <Field label={t('plan.description')} error={action.errors['description']}>
        <input autoFocus {...form.bind('description')} />
      </Field>
      <Field
        label={t('plan.unit')}
        hint={t('plan.unitHint')}
        error={action.errors['unit']}
      >
        <input maxLength={20} {...form.bind('unit')} />
      </Field>
      <Field
        label={t('plan.quantity')}
        hint={t('plan.quantityHint')}
        error={action.errors['quantity']}
      >
        <input inputMode="decimal" {...form.bind('quantity')} />
      </Field>
      <MoneyField
        label={t('plan.unitPrice')}
        currency={plan.project.currency}
        hint={
          preview ? t('plan.lineTotalPreview', {total: preview}) : undefined
        }
        error={action.errors['unitPrice']}
        value={form.values.unitPrice}
        onChange={(text) => form.set('unitPrice', text)}
      />
    </FormModal>
  );
}
