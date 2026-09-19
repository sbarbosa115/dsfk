import { Alert, TextField } from '@mui/material'
import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../api/client'
import { FormDialog } from '../components/FormDialog'
import { fieldError } from '../lib/errors'

export function ChangePasswordDialog({ onClose }: { onClose: () => void }) {
  const { t } = useTranslation()
  const [form, setForm] = useState({ currentPassword: '', newPassword: '' })
  const save = useMutation({ mutationFn: () => api<void>('/me/password', { method: 'POST', body: form }) })
  const field = (name: keyof typeof form) => ({
    value: form[name],
    type: 'password',
    onChange: (e: { target: { value: string } }) => setForm({ ...form, [name]: e.target.value }),
    error: !!fieldError(save.error, name),
    helperText: fieldError(save.error, name),
  })

  return (
    <FormDialog
      title={t('account.changePassword')}
      error={save.error}
      pending={save.isPending}
      disabled={save.isSuccess || !form.currentPassword || form.newPassword.length < 8}
      onSubmit={() => save.mutate()}
      onClose={onClose}
      maxWidth="xs"
    >
      {save.isSuccess && <Alert severity="success">{t('account.changed')}</Alert>}
      <TextField label={t('account.current')} autoComplete="current-password" autoFocus {...field('currentPassword')} />
      <TextField label={t('account.new')} autoComplete="new-password" {...field('newPassword')} />
    </FormDialog>
  )
}
