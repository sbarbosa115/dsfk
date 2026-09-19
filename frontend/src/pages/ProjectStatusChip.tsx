import { Chip } from '@mui/material'
import { useTranslation } from 'react-i18next'
import type { ProjectStatus } from '../api/types'

const COLORS = { DRAFT: 'default', ACTIVE: 'success', COMPLETED: 'primary', ARCHIVED: 'default' } as const

export function ProjectStatusChip({ status }: { status: ProjectStatus }) {
  const { t } = useTranslation()

  return <Chip size="small" label={t(`projectStatus.${status}`)} color={COLORS[status]} variant={status === 'ARCHIVED' ? 'outlined' : 'filled'} />
}
