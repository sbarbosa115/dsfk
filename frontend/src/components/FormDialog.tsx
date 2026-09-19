import { Alert, Button, Dialog, DialogActions, DialogContent, DialogTitle, Stack } from '@mui/material'
import type { FormEvent, ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { errorMessage } from '../lib/errors'

interface Props {
  title: string
  submitLabel?: string
  submitColor?: 'primary' | 'error' | 'success' | 'warning'
  error?: unknown
  pending?: boolean
  disabled?: boolean
  onSubmit: () => void
  onClose: () => void
  children?: ReactNode
  maxWidth?: 'xs' | 'sm' | 'md'
}

export function FormDialog({ title, submitLabel, submitColor = 'primary', error, pending, disabled, onSubmit, onClose, children, maxWidth = 'sm' }: Props) {
  const { t } = useTranslation()

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault()
    onSubmit()
  }

  return (
    <Dialog open onClose={onClose} fullWidth maxWidth={maxWidth}>
      <form onSubmit={handleSubmit} noValidate>
        <DialogTitle>{title}</DialogTitle>
        <DialogContent>
          <Stack spacing={2} sx={{ pt: 1 }}>
            {!!error && <Alert severity="error">{errorMessage(t, error)}</Alert>}
            {children}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>{t('common.cancel')}</Button>
          <Button type="submit" variant="contained" color={submitColor} disabled={pending || disabled}>
            {submitLabel ?? t('common.save')}
          </Button>
        </DialogActions>
      </form>
    </Dialog>
  )
}
