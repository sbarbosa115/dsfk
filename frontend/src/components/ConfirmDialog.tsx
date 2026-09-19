import { Typography } from '@mui/material'
import { useTranslation } from 'react-i18next'
import { FormDialog } from './FormDialog'

interface Props {
  title: string
  message: string
  confirmLabel?: string
  error?: unknown
  pending?: boolean
  onConfirm: () => void
  onClose: () => void
}

export function ConfirmDialog({ title, message, confirmLabel, error, pending, onConfirm, onClose }: Props) {
  const { t } = useTranslation()

  return (
    <FormDialog
      title={title}
      submitLabel={confirmLabel ?? t('common.remove')}
      submitColor="error"
      error={error}
      pending={pending}
      onSubmit={onConfirm}
      onClose={onClose}
      maxWidth="xs"
    >
      <Typography>{message}</Typography>
    </FormDialog>
  )
}
