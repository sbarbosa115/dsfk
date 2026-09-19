import { Box, Divider, Link, List, ListItem, ListItemText, MenuItem, Stack, TextField, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { api, upload } from '../../api/client'
import type { Expense, Plan } from '../../api/types'
import { FormDialog } from '../../components/FormDialog'
import { fieldError } from '../../lib/errors'
import { formatDate, formatMoney, parseAmount, today } from '../../lib/format'
import { FilePicker } from '../finance/dialogs'
import { useMoneyMutation } from './useExpenses'

const dateSlotProps = { inputLabel: { shrink: true }, htmlInput: { max: today() } }

interface ExpenseDialogProps {
  projectId: string
  plan: Plan
  isTeamLead: boolean
  expense?: Expense
  onClose: () => void
}

export function ExpenseDialog({ projectId, plan, isTeamLead, expense, onClose }: ExpenseDialogProps) {
  const { t } = useTranslation()
  const openStages = plan.stages.filter((s) => s.status !== 'COMPLETED' || expense?.stage.id === s.id)
  const [form, setForm] = useState({
    stageId: String(expense?.stage.id ?? openStages.find((s) => s.status === 'IN_PROGRESS')?.id ?? openStages[0]?.id ?? ''),
    categoryId: String(expense?.category.id ?? plan.categories[0]?.id ?? ''),
    date: expense?.date ?? today(),
    amount: expense ? String(Number(expense.amount)) : '',
    description: expense?.description ?? '',
    supplier: expense?.supplier ?? '',
    invoiceNumber: expense?.invoiceNumber ?? '',
    paidFrom: expense?.paidFrom ?? (isTeamLead ? 'OUT_OF_POCKET' : 'STAGE'),
  })
  const [file, setFile] = useState<File | null>(null)

  const save = useMoneyMutation(
    projectId,
    async () => {
      const body = { ...form, stageId: Number(form.stageId), categoryId: Number(form.categoryId), amount: parseAmount(form.amount) }
      const saved = expense
        ? await api<Expense>(`/expenses/${expense.id}`, { method: 'PUT', body })
        : await api<Expense>(`/projects/${projectId}/expenses`, { method: 'POST', body })
      if (file) {
        await upload(`/expenses/${saved.id}/attachments`, file)
      }

      return saved
    },
    onClose,
  )
  const field = (name: keyof typeof form) => ({
    value: form[name],
    onChange: (e: { target: { value: string } }) => setForm({ ...form, [name]: e.target.value }),
    error: !!fieldError(save.error, name),
    helperText: fieldError(save.error, name),
  })

  return (
    <FormDialog
      title={expense ? t('expenses.edit') : t('expenses.new')}
      error={save.error}
      pending={save.isPending}
      disabled={!form.description.trim() || !form.amount}
      onSubmit={() => save.mutate(undefined)}
      onClose={onClose}
    >
      {isTeamLead && !expense && (
        <Typography variant="body2" color="text.secondary">
          {t('expenses.teamLeadNote')}
        </Typography>
      )}
      <TextField label={t('expenses.description')} required autoFocus {...field('description')} />
      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
        <TextField label={`${t('expenses.amount')} (${plan.project.currency})`} slotProps={{ htmlInput: { inputMode: 'decimal' } }} {...field('amount')} />
        <TextField label={t('expenses.date')} type="date" slotProps={dateSlotProps} {...field('date')} />
      </Stack>
      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
        <TextField select label={t('expenses.stage')} {...field('stageId')}>
          {openStages.map((s) => (
            <MenuItem key={s.id} value={String(s.id)}>
              {s.name}
            </MenuItem>
          ))}
        </TextField>
        <TextField select label={t('expenses.category')} {...field('categoryId')}>
          {plan.categories.map((c) => (
            <MenuItem key={c.id} value={String(c.id)}>
              {c.name}
            </MenuItem>
          ))}
        </TextField>
      </Stack>
      {!isTeamLead && (
        <TextField select label={t('expenses.paidFromLabel')} {...field('paidFrom')} disabled={!!expense}>
          <MenuItem value="STAGE">{t('expenses.paidFrom.STAGE')}</MenuItem>
          <MenuItem value="PETTY_CASH">{t('expenses.paidFrom.PETTY_CASH')}</MenuItem>
        </TextField>
      )}
      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
        <TextField label={t('expenses.supplier')} {...field('supplier')} />
        <TextField label={t('expenses.invoiceNumber')} {...field('invoiceNumber')} />
      </Stack>
      <FilePicker file={file} onChange={setFile} error={fieldError(save.error, 'file')} />
    </FormDialog>
  )
}

export function ReasonDialog({ title, text, label, submitLabel, onSubmit, onClose, error, pending }: {
  title: string
  text?: string
  label: string
  submitLabel: string
  onSubmit: (reason: string) => void
  onClose: () => void
  error: unknown
  pending: boolean
}) {
  const [reason, setReason] = useState('')

  return (
    <FormDialog title={title} submitLabel={submitLabel} submitColor="error" error={error} pending={pending} disabled={!reason.trim()} onSubmit={() => onSubmit(reason)} onClose={onClose} maxWidth="xs">
      {text && <Typography variant="body2">{text}</Typography>}
      <TextField label={label} required autoFocus multiline minRows={2} value={reason} onChange={(e) => setReason(e.target.value)} />
    </FormDialog>
  )
}

export function AttachReceiptDialog({ projectId, expense, onClose }: { projectId: string; expense: Expense; onClose: () => void }) {
  const { t } = useTranslation()
  const [file, setFile] = useState<File | null>(null)
  const save = useMoneyMutation(projectId, (f: File) => upload(`/expenses/${expense.id}/attachments`, f), onClose)

  return (
    <FormDialog title={t('expenses.attach')} error={save.error} pending={save.isPending} disabled={!file} onSubmit={() => file && save.mutate(file)} onClose={onClose} maxWidth="xs">
      <FilePicker file={file} onChange={setFile} error={fieldError(save.error, 'file')} />
    </FormDialog>
  )
}

export function ReimburseDialog({ projectId, expenses, currency, onClose }: { projectId: string; expenses: Expense[]; currency: string; onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({ date: today(), method: 'CASH', reference: '' })
  const total = expenses.reduce((sum, e) => sum + Number(e.amount), 0)
  const save = useMoneyMutation(projectId, () => api(`/projects/${projectId}/reimbursements`, { method: 'POST', body: { ...form, expenseIds: expenses.map((e) => e.id) } }), onClose)

  return (
    <FormDialog title={t('expenses.reimburseTitle')} error={save.error} pending={save.isPending} onSubmit={() => save.mutate(undefined)} onClose={onClose}>
      <List dense disablePadding>
        {expenses.map((e) => (
          <ListItem key={e.id} disableGutters secondaryAction={formatMoney(e.amount, currency)}>
            <ListItemText primary={e.description} secondary={e.paidBy.fullName} />
          </ListItem>
        ))}
      </List>
      <Typography sx={{ fontWeight: 700 }}>{t('expenses.reimburseTotal', { amount: formatMoney(total, currency) })}</Typography>
      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
        <TextField label={t('expenses.date')} type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} slotProps={dateSlotProps} />
        <TextField select label={t('expenses.method')} value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })}>
          {(['CASH', 'TRANSFER', 'CHECK', 'OTHER'] as const).map((m) => (
            <MenuItem key={m} value={m}>
              {t(`finance.method.${m}`)}
            </MenuItem>
          ))}
        </TextField>
        <TextField label={t('expenses.reference')} value={form.reference} onChange={(e) => setForm({ ...form, reference: e.target.value })} />
      </Stack>
    </FormDialog>
  )
}

export function ExpenseDetailDialog({ expenseId, currency, onClose }: { expenseId: number; currency: string; onClose: () => void }) {
  const { t } = useTranslation()
  const expense = useQuery({ queryKey: ['expense', expenseId], queryFn: () => api<Expense>(`/expenses/${expenseId}`) })
  const e = expense.data

  return (
    <FormDialog title={t('expenses.details')} submitLabel={t('common.back')} onSubmit={onClose} onClose={onClose} error={expense.error}>
      {e && (
        <>
          <Box>
            <Typography variant="h6">{formatMoney(e.amount, currency)}</Typography>
            <Typography>{e.description}</Typography>
            <Typography variant="body2" color="text.secondary">
              {formatDate(e.date)} · {e.stage.name} · {e.category.name}
              {e.supplier && ` · ${e.supplier}`}
              {e.invoiceNumber && ` · ${e.invoiceNumber}`}
            </Typography>
          </Box>
          {e.attachments.map((a) => (
            <Link key={a.id} href={`/api/attachments/${a.id}`} target="_blank" rel="noopener">
              {a.name}
            </Link>
          ))}
          <Divider />
          <Typography variant="subtitle2">{t('expenses.history')}</Typography>
          <List dense disablePadding>
            {e.events?.map((ev, i) => (
              <ListItem key={i} disableGutters>
                <ListItemText
                  primary={`${formatDate(ev.createdAt)} · ${t(`expenses.event.${ev.type}`, { user: ev.user })}`}
                  secondary={
                    <>
                      {ev.comment && <span>“{ev.comment}”</span>}
                      {ev.previous && (
                        <span>
                          {t('expenses.previousValues', {
                            values: `${formatMoney(ev.previous.amount ?? 0, currency)} · ${ev.previous.description} · ${ev.previous.stage} · ${ev.previous.category}`,
                          })}
                        </span>
                      )}
                    </>
                  }
                />
              </ListItem>
            ))}
          </List>
        </>
      )}
    </FormDialog>
  )
}
