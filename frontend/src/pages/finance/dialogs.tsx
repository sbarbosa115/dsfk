import AddIcon from '@mui/icons-material/Add'
import AttachIcon from '@mui/icons-material/AttachFileOutlined'
import DeleteIcon from '@mui/icons-material/DeleteOutlined'
import { Box, Button, IconButton, MenuItem, Stack, TextField, Typography } from '@mui/material'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { api, upload } from '../../api/client'
import type { Finance, Movement, Plan, StageFinance } from '../../api/types'
import { FormDialog } from '../../components/FormDialog'
import { fieldError } from '../../lib/errors'
import { formatMoney, parseAmount, today } from '../../lib/format'
import { useFinanceMutation } from './useFinance'

const METHODS = ['TRANSFER', 'CASH', 'CHECK', 'OTHER'] as const
const dateSlotProps = { inputLabel: { shrink: true }, htmlInput: { max: today() } }

interface AllocationRow {
  destination: string // "STAGE:<id>" | "PETTY_CASH" | "CONTINGENCY"
  categoryId: string
  amount: string
}

export function DepositDialog({ projectId, finance, plan, onClose }: { projectId: string; finance: Finance; plan: Plan; onClose: () => void }) {
  const { t } = useTranslation()
  const openStages = finance.stages.filter((s) => s.status !== 'COMPLETED')
  const [form, setForm] = useState({ date: today(), method: 'TRANSFER', reference: '', note: '' })
  const [rows, setRows] = useState<AllocationRow[]>([{ destination: openStages[0] ? `STAGE:${openStages[0].id}` : 'PETTY_CASH', categoryId: '', amount: '' }])
  const [file, setFile] = useState<File | null>(null)

  const save = useFinanceMutation(
    projectId,
    async () => {
      const movement = await api<Movement>(`/projects/${projectId}/deposits`, {
        method: 'POST',
        body: {
          ...form,
          allocations: rows.map((r) => {
            const [destination, stageId] = r.destination.split(':')

            return {
              destination,
              stageId: stageId ? Number(stageId) : null,
              categoryId: destination === 'STAGE' && r.categoryId ? Number(r.categoryId) : null,
              amount: parseAmount(r.amount),
            }
          }),
        },
      })
      if (file) {
        await upload(`/movements/${movement.id}/attachments`, file)
      }

      return movement
    },
    onClose,
  )

  const total = rows.reduce((sum, r) => sum + (Number(parseAmount(r.amount)) || 0), 0)
  const setRow = (i: number, patch: Partial<AllocationRow>) => setRows(rows.map((r, j) => (j === i ? { ...r, ...patch } : r)))
  const stageHint = (destination: string) => {
    const stage = finance.stages.find((s) => `STAGE:${s.id}` === destination)
    const missing = stage ? Number(stage.budget) - Number(stage.received) : 0

    return missing > 0 ? t('finance.stageRemaining', { amount: formatMoney(missing, finance.currency) }) : undefined
  }

  return (
    <FormDialog title={t('finance.newDeposit')} error={save.error} pending={save.isPending} disabled={total <= 0} onSubmit={() => save.mutate(undefined)} onClose={onClose} maxWidth="md">
      <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
        <TextField label={t('finance.date')} type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} slotProps={dateSlotProps} error={!!fieldError(save.error, 'date')} helperText={fieldError(save.error, 'date')} />
        <TextField select label={t('finance.paymentMethod')} value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })}>
          {METHODS.map((m) => (
            <MenuItem key={m} value={m}>
              {t(`finance.method.${m}`)}
            </MenuItem>
          ))}
        </TextField>
        <TextField label={t('finance.reference')} value={form.reference} onChange={(e) => setForm({ ...form, reference: e.target.value })} />
      </Stack>

      <Box>
        <Typography variant="subtitle2">{t('finance.allocations')}</Typography>
        <Typography variant="caption" color="text.secondary">
          {t('finance.allocationsHint')}
        </Typography>
        {fieldError(save.error, 'allocations') && (
          <Typography variant="caption" color="error" sx={{ display: 'block' }}>
            {fieldError(save.error, 'allocations')}
          </Typography>
        )}
      </Box>
      {rows.map((row, i) => {
        const isStage = row.destination.startsWith('STAGE:')
        const err = (f: string) => fieldError(save.error, `allocations[${i}].${f}`)

        return (
          <Stack key={i} direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ alignItems: { sm: 'flex-start' } }}>
            <TextField select label={t('finance.destination')} value={row.destination} onChange={(e) => setRow(i, { destination: e.target.value, categoryId: '' })} error={!!err('stageId')} helperText={err('stageId') ?? stageHint(row.destination)}>
              {openStages.map((s) => (
                <MenuItem key={s.id} value={`STAGE:${s.id}`}>
                  {t('finance.stage')}: {s.name}
                </MenuItem>
              ))}
              <MenuItem value="PETTY_CASH">{t('finance.pettyCash')}</MenuItem>
              <MenuItem value="CONTINGENCY">{t('finance.contingency')}</MenuItem>
            </TextField>
            <TextField select label={t('finance.categoryOptional')} value={row.categoryId} onChange={(e) => setRow(i, { categoryId: e.target.value })} disabled={!isStage}>
              <MenuItem value="">{t('finance.none')}</MenuItem>
              {plan.categories.map((c) => (
                <MenuItem key={c.id} value={String(c.id)}>
                  {c.name}
                </MenuItem>
              ))}
            </TextField>
            <TextField label={`${t('finance.amount')} (${finance.currency})`} value={row.amount} onChange={(e) => setRow(i, { amount: e.target.value })} slotProps={{ htmlInput: { inputMode: 'decimal' } }} error={!!err('amount')} helperText={err('amount')} />
            <IconButton aria-label={t('common.remove')} onClick={() => setRows(rows.filter((_, j) => j !== i))} disabled={rows.length === 1} sx={{ mt: { sm: 0.5 } }}>
              <DeleteIcon />
            </IconButton>
          </Stack>
        )
      })}
      <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between' }}>
        <Button startIcon={<AddIcon />} onClick={() => setRows([...rows, { destination: 'PETTY_CASH', categoryId: '', amount: '' }])}>
          {t('finance.addAllocation')}
        </Button>
        <Typography sx={{ fontWeight: 700 }}>
          {t('finance.total')}: {formatMoney(total, finance.currency)}
        </Typography>
      </Stack>

      <TextField label={t('finance.note')} value={form.note} onChange={(e) => setForm({ ...form, note: e.target.value })} multiline minRows={2} />
      <FilePicker file={file} onChange={setFile} error={fieldError(save.error, 'file')} />
    </FormDialog>
  )
}

export function FilePicker({ file, onChange, error }: { file: File | null; onChange: (file: File | null) => void; error?: string }) {
  const { t } = useTranslation()

  return (
    <Box>
      <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 0.5 }}>
        {t('finance.proof')}
      </Typography>
      <Button component="label" variant="outlined" size="small" startIcon={<AttachIcon />}>
        {file ? file.name : t('finance.chooseFile')}
        <input hidden type="file" accept="application/pdf,image/*" onChange={(e) => onChange(e.target.files?.[0] ?? null)} />
      </Button>
      {error && (
        <Typography variant="caption" color="error" sx={{ display: 'block' }}>
          {error}
        </Typography>
      )}
    </Box>
  )
}

export function DrawDialog({ projectId, finance, onClose }: { projectId: string; finance: Finance; onClose: () => void }) {
  const { t } = useTranslation()
  const openStages = finance.stages.filter((s) => s.status !== 'COMPLETED')
  const [form, setForm] = useState({ stageId: String(openStages[0]?.id ?? ''), amount: '', date: today(), reason: '' })
  const save = useFinanceMutation(
    projectId,
    () => api(`/projects/${projectId}/contingency/draws`, { method: 'POST', body: { ...form, stageId: Number(form.stageId), amount: parseAmount(form.amount) } }),
    onClose,
  )
  const err = (f: string) => fieldError(save.error, f)

  return (
    <FormDialog title={t('finance.draw')} error={save.error} pending={save.isPending} disabled={!form.reason.trim() || !form.amount} onSubmit={() => save.mutate(undefined)} onClose={onClose}>
      <Typography variant="body2" color="text.secondary">
        {t('finance.drawAvailable', { amount: formatMoney(finance.contingency.balance, finance.currency) })}
      </Typography>
      <TextField select label={t('finance.drawStage')} value={form.stageId} onChange={(e) => setForm({ ...form, stageId: e.target.value })} error={!!err('stageId')} helperText={err('stageId')}>
        {openStages.map((s) => (
          <MenuItem key={s.id} value={String(s.id)}>
            {s.name}
          </MenuItem>
        ))}
      </TextField>
      <TextField label={`${t('finance.amount')} (${finance.currency})`} value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} slotProps={{ htmlInput: { inputMode: 'decimal' } }} error={!!err('amount')} helperText={err('amount')} />
      <TextField label={t('finance.date')} type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} slotProps={dateSlotProps} error={!!err('date')} helperText={err('date')} />
      <TextField label={t('finance.reason')} required multiline minRows={2} value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} error={!!err('reason')} helperText={err('reason')} />
    </FormDialog>
  )
}

export function VoidDialog({ projectId, movement, onClose }: { projectId: string; movement: Movement; onClose: () => void }) {
  const { t } = useTranslation()
  const [reason, setReason] = useState('')
  const save = useFinanceMutation(projectId, () => api(`/movements/${movement.id}/void`, { method: 'POST', body: { reason } }), onClose)

  return (
    <FormDialog title={t('finance.voidTitle')} submitLabel={t('finance.void')} submitColor="error" error={save.error} pending={save.isPending} disabled={!reason.trim()} onSubmit={() => save.mutate(undefined)} onClose={onClose} maxWidth="xs">
      <Typography variant="body2">{t('finance.voidText')}</Typography>
      <TextField label={t('finance.reason')} required autoFocus multiline minRows={2} value={reason} onChange={(e) => setReason(e.target.value)} />
    </FormDialog>
  )
}

export function AttachDialog({ projectId, movement, onClose }: { projectId: string; movement: Movement; onClose: () => void }) {
  const { t } = useTranslation()
  const [file, setFile] = useState<File | null>(null)
  const save = useFinanceMutation(projectId, (f: File) => upload(`/movements/${movement.id}/attachments`, f), onClose)

  return (
    <FormDialog title={t('finance.attach')} error={save.error} pending={save.isPending} disabled={!file} onSubmit={() => file && save.mutate(file)} onClose={onClose} maxWidth="xs">
      <FilePicker file={file} onChange={setFile} error={fieldError(save.error, 'file')} />
    </FormDialog>
  )
}

export function CompleteStageDialog({ projectId, finance, stage, onClose }: { projectId: string; finance: Finance; stage: StageFinance; onClose: () => void }) {
  const { t } = useTranslation()
  const [actualEnd, setActualEnd] = useState(today())
  const save = useFinanceMutation(projectId, () => api(`/stages/${stage.id}/complete`, { method: 'POST', body: { actualEnd } }), onClose)
  const available = Number(stage.available)
  const amount = formatMoney(stage.available, finance.currency)

  return (
    <FormDialog title={`${t('finance.completeStage')} · ${stage.name}`} submitLabel={t('finance.completeStage')} error={save.error} pending={save.isPending} onSubmit={() => save.mutate(undefined)} onClose={onClose}>
      <Typography>{t('finance.completeStageText', { stage: stage.name })}</Typography>
      <Typography color="text.secondary">
        {available <= 0 ? t('finance.nothingToCarry') : stage.nextStage ? t('finance.carryoverNext', { amount, next: stage.nextStage }) : t('finance.carryoverContingency', { amount })}
      </Typography>
      <TextField label={t('finance.actualEnd')} type="date" value={actualEnd} onChange={(e) => setActualEnd(e.target.value)} slotProps={dateSlotProps} error={!!fieldError(save.error, 'actualEnd')} helperText={fieldError(save.error, 'actualEnd')} />
    </FormDialog>
  )
}
