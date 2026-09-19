import { MenuItem, TextField } from '@mui/material'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import type { BudgetLine, Milestone, Plan, Stage } from '../../api/types'
import { FormDialog } from '../../components/FormDialog'
import { fieldError } from '../../lib/errors'
import { formatMoney, parseAmount, today } from '../../lib/format'
import { usePlanMutation } from './usePlan'

const dateProps = { type: 'date', slotProps: { inputLabel: { shrink: true } } } as const

/** Text-field props bound to one key of a form state object, with its API validation error. */
function bind<T extends Record<string, string>>(form: T, setForm: (f: T) => void, error: unknown) {
  return (field: keyof T & string) => ({
    value: form[field],
    onChange: (e: { target: { value: string } }) => setForm({ ...form, [field]: e.target.value }),
    error: !!fieldError(error, field),
    helperText: fieldError(error, field),
  })
}

export function StageDialog({ plan, stage, onClose }: { plan: Plan; stage?: Stage; onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({ name: stage?.name ?? '', plannedStart: stage?.plannedStart ?? '', plannedEnd: stage?.plannedEnd ?? '' })
  const datesLocked = !plan.permissions.edit
  const save = usePlanMutation(
    plan.project.id,
    () => {
      const body = datesLocked ? { name: form.name } : { name: form.name, plannedStart: form.plannedStart || null, plannedEnd: form.plannedEnd || null }

      return stage ? { path: `/stages/${stage.id}`, method: 'PATCH', body } : { path: `/projects/${plan.project.id}/stages`, method: 'POST', body }
    },
    onClose,
  )
  const field = bind(form, setForm, save.error)

  return (
    <FormDialog
      title={stage ? t('plan.editStage') : t('plan.addStage')}
      error={save.error}
      pending={save.isPending}
      disabled={!form.name.trim()}
      onSubmit={() => save.mutate()}
      onClose={onClose}
    >
      <TextField label={t('plan.stageName')} required autoFocus {...field('name')} />
      <TextField label={t('projects.plannedStart')} {...dateProps} disabled={datesLocked} {...field('plannedStart')} />
      <TextField label={t('projects.plannedEnd')} {...dateProps} disabled={datesLocked} {...field('plannedEnd')} />
    </FormDialog>
  )
}

export function LineDialog({ plan, stage, line, onClose }: { plan: Plan; stage: Stage; line?: BudgetLine; onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({
    categoryId: String(line?.categoryId ?? plan.categories[0]?.id ?? ''),
    description: line?.description ?? '',
    unit: line?.unit ?? '',
    quantity: line?.quantity ?? '',
    unitPrice: line ? String(Number(line.unitPrice)) : '',
  })
  const save = usePlanMutation(
    plan.project.id,
    () => {
      const body = { ...form, categoryId: Number(form.categoryId), quantity: form.quantity.replace(',', '.'), unitPrice: parseAmount(form.unitPrice) }

      return line ? { path: `/budget-lines/${line.id}`, method: 'PUT', body } : { path: `/stages/${stage.id}/lines`, method: 'POST', body }
    },
    onClose,
  )
  const field = bind(form, setForm, save.error)
  const total = Number(form.quantity.replace(',', '.')) * Number(parseAmount(form.unitPrice))

  return (
    <FormDialog
      title={line ? t('plan.editLine') : `${t('plan.addLine')} · ${stage.name}`}
      error={save.error}
      pending={save.isPending}
      onSubmit={() => save.mutate()}
      onClose={onClose}
    >
      <TextField select label={t('plan.category')} {...field('categoryId')}>
        {plan.categories.map((c) => (
          <MenuItem key={c.id} value={String(c.id)}>
            {c.name}
          </MenuItem>
        ))}
      </TextField>
      <TextField label={t('plan.description')} required autoFocus {...field('description')} />
      <TextField label={t('plan.unit')} placeholder="m³, kg, día, global…" {...field('unit')} />
      <TextField label={t('plan.quantity')} slotProps={{ htmlInput: { inputMode: 'decimal' } }} {...field('quantity')} />
      <TextField label={`${t('plan.unitPrice')} (${plan.project.currency})`} slotProps={{ htmlInput: { inputMode: 'decimal' } }} {...field('unitPrice')} />
      <TextField label={t('plan.lineTotal')} value={Number.isFinite(total) ? formatMoney(total, plan.project.currency) : '—'} disabled />
    </FormDialog>
  )
}

export function MilestoneDialog({ plan, stage, milestone, onClose }: { plan: Plan; stage: Stage; milestone?: Milestone; onClose: () => void }) {
  const { t } = useTranslation()
  const remaining = 10000 - stage.milestoneWeightTotal + (milestone?.weight ?? 0)
  const [form, setForm] = useState({
    name: milestone?.name ?? '',
    weight: milestone ? String(milestone.weight / 100) : remaining > 0 ? String(remaining / 100) : '',
    plannedDate: milestone?.plannedDate ?? '',
  })
  const save = usePlanMutation(
    plan.project.id,
    () => {
      const body = { name: form.name, weight: form.weight.replace(',', '.'), plannedDate: form.plannedDate || null }

      return milestone ? { path: `/milestones/${milestone.id}`, method: 'PATCH', body } : { path: `/stages/${stage.id}/milestones`, method: 'POST', body }
    },
    onClose,
  )
  const field = bind(form, setForm, save.error)

  return (
    <FormDialog
      title={milestone ? t('plan.editMilestone') : `${t('plan.addMilestone')} · ${stage.name}`}
      error={save.error}
      pending={save.isPending}
      disabled={!form.name.trim()}
      onSubmit={() => save.mutate()}
      onClose={onClose}
    >
      <TextField label={t('plan.milestoneName')} required autoFocus {...field('name')} />
      <TextField
        label={t('plan.weight')}
        slotProps={{ htmlInput: { inputMode: 'decimal' } }}
        {...field('weight')}
        helperText={fieldError(save.error, 'weight') ?? t('plan.weightHint')}
      />
      <TextField label={t('plan.plannedDate')} {...dateProps} {...field('plannedDate')} />
    </FormDialog>
  )
}

export function CompleteMilestoneDialog({ plan, milestone, onClose }: { plan: Plan; milestone: Milestone; onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({ completedAt: today(), notes: '' })
  const save = usePlanMutation(plan.project.id, () => ({ path: `/milestones/${milestone.id}/complete`, method: 'POST', body: form }), onClose)
  const field = bind(form, setForm, save.error)

  return (
    <FormDialog title={`${t('plan.completeTitle')} · ${milestone.name}`} submitColor="success" submitLabel={t('plan.complete')} error={save.error} pending={save.isPending} onSubmit={() => save.mutate()} onClose={onClose}>
      <TextField label={t('plan.completedAt')} {...dateProps} slotProps={{ inputLabel: { shrink: true }, htmlInput: { max: today() } }} {...field('completedAt')} />
      <TextField label={t('plan.notes')} multiline minRows={2} {...field('notes')} />
    </FormDialog>
  )
}

export function StartStageDialog({ plan, stage, onClose }: { plan: Plan; stage: Stage; onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({ actualStart: today() })
  const save = usePlanMutation(plan.project.id, () => ({ path: `/stages/${stage.id}/start`, method: 'POST', body: form }), onClose)
  const field = bind(form, setForm, save.error)

  return (
    <FormDialog title={`${t('plan.startStage')} · ${stage.name}`} submitLabel={t('plan.startStage')} error={save.error} pending={save.isPending} onSubmit={() => save.mutate()} onClose={onClose} maxWidth="xs">
      <TextField label={t('plan.actualStart')} {...dateProps} slotProps={{ inputLabel: { shrink: true }, htmlInput: { max: today() } }} {...field('actualStart')} />
    </FormDialog>
  )
}

export function ContingencyDialog({ plan, onClose }: { plan: Plan; onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({ contingency: String(Number(plan.budget?.contingency ?? 0)) })
  const save = usePlanMutation(
    plan.project.id,
    () => ({ path: `/projects/${plan.project.id}/budget/contingency`, method: 'PUT', body: { contingency: parseAmount(form.contingency) } }),
    onClose,
  )
  const field = bind(form, setForm, save.error)

  return (
    <FormDialog title={t('plan.editContingency')} error={save.error} pending={save.isPending} onSubmit={() => save.mutate()} onClose={onClose} maxWidth="xs">
      <TextField label={`${t('plan.contingency')} (${plan.project.currency})`} autoFocus slotProps={{ htmlInput: { inputMode: 'decimal' } }} {...field('contingency')} />
    </FormDialog>
  )
}

export function ReturnBudgetDialog({ plan, onClose }: { plan: Plan; onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({ comment: '' })
  const save = usePlanMutation(plan.project.id, () => ({ path: `/projects/${plan.project.id}/budget/return`, method: 'POST', body: form }), onClose)
  const field = bind(form, setForm, save.error)

  return (
    <FormDialog
      title={t('plan.returnBudget')}
      submitLabel={t('plan.returnBudget')}
      submitColor="warning"
      error={save.error}
      pending={save.isPending}
      disabled={!form.comment.trim()}
      onSubmit={() => save.mutate()}
      onClose={onClose}
    >
      <TextField label={t('plan.returnComment')} required autoFocus multiline minRows={3} {...field('comment')} />
    </FormDialog>
  )
}
