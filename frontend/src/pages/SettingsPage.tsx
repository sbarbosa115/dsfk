import { Alert, Button, Paper, Stack, TextField, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../api/client'
import type { Settings } from '../api/types'
import { PageHeader } from '../layout/PageHeader'
import { errorMessage, fieldError } from '../lib/errors'

export function SettingsPage() {
  const { t } = useTranslation()
  const settings = useQuery({ queryKey: ['settings'], queryFn: () => api<Settings>('/settings') })

  return (
    <>
      <PageHeader title={t('settings.title')} />
      {settings.error && <Alert severity="error">{errorMessage(t, settings.error)}</Alert>}
      {settings.data ? <SettingsForm initial={settings.data} /> : <Typography color="text.secondary">{t('common.loading')}</Typography>}
    </>
  )
}

/** Parses "80, 100" into [80, 100]; invalid entries are sent as-is so the API rejects them. */
export function parsePercents(value: string): number[] {
  return value
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean)
    .map(Number)
}

function SettingsForm({ initial }: { initial: Settings }) {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    defaultCurrency: initial.defaultCurrency,
    teamLeadExpenseLimit: initial.teamLeadExpenseLimit,
    pettyCashLowBalancePercent: String(initial.pettyCashLowBalancePercent),
    budgetWarningPercents: initial.budgetWarningPercents.join(', '),
  })

  const save = useMutation({
    mutationFn: () =>
      api<Settings>('/settings', {
        method: 'PUT',
        body: {
          defaultCurrency: form.defaultCurrency.toUpperCase(),
          teamLeadExpenseLimit: form.teamLeadExpenseLimit,
          pettyCashLowBalancePercent: Number(form.pettyCashLowBalancePercent),
          budgetWarningPercents: parsePercents(form.budgetWarningPercents),
        },
      }),
    onSuccess: (saved) => queryClient.setQueryData(['settings'], saved),
  })

  const field = (name: keyof typeof form) => ({
    value: form[name],
    onChange: (e: { target: { value: string } }) => setForm((f) => ({ ...f, [name]: e.target.value })),
    error: !!fieldError(save.error, name),
    helperText: fieldError(save.error, name) ?? t(`settings.${name}Hint`),
    label: t(`settings.${name}`),
  })

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault()
    save.mutate()
  }

  return (
    <Paper sx={{ p: 3, maxWidth: 560 }}>
      <Stack component="form" spacing={2.5} onSubmit={handleSubmit} noValidate>
        {save.isSuccess && <Alert severity="success">{t('common.saved')}</Alert>}
        {save.error && <Alert severity="error">{errorMessage(t, save.error)}</Alert>}
        <TextField {...field('defaultCurrency')} slotProps={{ htmlInput: { maxLength: 3, style: { textTransform: 'uppercase' } } }} />
        <TextField {...field('teamLeadExpenseLimit')} slotProps={{ htmlInput: { inputMode: 'decimal' } }} />
        <TextField {...field('pettyCashLowBalancePercent')} type="number" />
        <TextField {...field('budgetWarningPercents')} />
        <Stack direction="row" sx={{ justifyContent: 'flex-end' }}>
          <Button type="submit" variant="contained" disabled={save.isPending}>
            {t('common.save')}
          </Button>
        </Stack>
      </Stack>
    </Paper>
  )
}
