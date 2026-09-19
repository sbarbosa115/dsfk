import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  MenuItem,
  Stack,
  TextField,
} from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../api/client'
import type { Project, ProjectStatus, Settings } from '../api/types'
import { errorMessage, fieldError } from '../lib/errors'
import { toDateInput } from '../lib/format'

const STATUSES: ProjectStatus[] = ['DRAFT', 'ACTIVE', 'COMPLETED', 'ARCHIVED']

interface Props {
  project?: Project
  onClose: () => void
  onSaved?: (project: Project) => void
}

/** Create (no project) or edit (with project) dialog. Admin only. */
export function ProjectFormDialog({ project, onClose, onSaved }: Props) {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const settings = useQuery({ queryKey: ['settings'], queryFn: () => api<Settings>('/settings'), enabled: !project })

  const [form, setForm] = useState({
    name: project?.name ?? '',
    description: project?.description ?? '',
    currency: project?.currency ?? '',
    status: project?.status ?? 'DRAFT',
    plannedStart: toDateInput(project?.plannedStart),
    plannedEnd: toDateInput(project?.plannedEnd),
  })
  const currency = form.currency || settings.data?.defaultCurrency || ''

  const save = useMutation({
    mutationFn: () => {
      const body = {
        name: form.name,
        description: form.description,
        status: form.status,
        plannedStart: form.plannedStart || null,
        plannedEnd: form.plannedEnd || null,
        ...(project ? {} : { currency: currency.toUpperCase() }),
      }

      return project
        ? api<Project>(`/projects/${project.id}`, { method: 'PATCH', body })
        : api<Project>('/projects', { method: 'POST', body })
    },
    onSuccess: (saved) => {
      void queryClient.invalidateQueries({ queryKey: ['projects'] })
      void queryClient.invalidateQueries({ queryKey: ['me'] })
      onSaved?.(saved)
      onClose()
    },
  })

  const set = (field: keyof typeof form) => (e: { target: { value: string } }) =>
    setForm((f) => ({ ...f, [field]: e.target.value }))

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault()
    save.mutate()
  }

  return (
    <Dialog open onClose={onClose} fullWidth maxWidth="sm">
      <form onSubmit={handleSubmit} noValidate>
        <DialogTitle>{project ? t('common.edit') : t('projects.new')}</DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ pt: 1 }}>
            {save.error && <Alert severity="error">{errorMessage(t, save.error)}</Alert>}
            <TextField
              label={t('projects.name')}
              value={form.name}
              onChange={set('name')}
              required
              autoFocus
              error={!!fieldError(save.error, 'name')}
              helperText={fieldError(save.error, 'name')}
            />
            <TextField label={t('projects.description')} value={form.description} onChange={set('description')} multiline minRows={2} />
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
              <TextField
                label={t('projects.currency')}
                value={currency}
                onChange={set('currency')}
                disabled={!!project}
                slotProps={{ htmlInput: { maxLength: 3, style: { textTransform: 'uppercase' } } }}
                error={!!fieldError(save.error, 'currency')}
                helperText={fieldError(save.error, 'currency') ?? t('projects.currencyHint')}
              />
              <TextField select label={t('projects.status')} value={form.status} onChange={set('status')}>
                {STATUSES.map((s) => (
                  <MenuItem key={s} value={s}>
                    {t(`projectStatus.${s}`)}
                  </MenuItem>
                ))}
              </TextField>
            </Stack>
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
              <TextField
                label={t('projects.plannedStart')}
                type="date"
                value={form.plannedStart}
                onChange={set('plannedStart')}
                slotProps={{ inputLabel: { shrink: true } }}
              />
              <TextField
                label={t('projects.plannedEnd')}
                type="date"
                value={form.plannedEnd}
                onChange={set('plannedEnd')}
                slotProps={{ inputLabel: { shrink: true } }}
                error={!!fieldError(save.error, 'plannedEnd')}
                helperText={fieldError(save.error, 'plannedEnd')}
              />
            </Stack>
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>{t('common.cancel')}</Button>
          <Button type="submit" variant="contained" disabled={save.isPending || !form.name.trim()}>
            {project ? t('common.save') : t('common.create')}
          </Button>
        </DialogActions>
      </form>
    </Dialog>
  )
}
