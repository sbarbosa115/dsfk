import CheckIcon from '@mui/icons-material/CheckCircleOutlined'
import ErrorIcon from '@mui/icons-material/ErrorOutlined'
import WarningIcon from '@mui/icons-material/ReportProblemOutlined'
import { Chip, Tooltip } from '@mui/material'
import { useTranslation } from 'react-i18next'

export type Health = 'good' | 'warning' | 'critical'

/** CPI / SPI: 1 or more is on track, 0.9–1 needs attention, below 0.9 is critical. */
export function healthOf(index: number | null): Health | null {
  if (null === index) {
    return null
  }

  return index >= 1 ? 'good' : index >= 0.9 ? 'warning' : 'critical'
}

const STYLE = {
  good: { color: 'success', icon: <CheckIcon /> },
  warning: { color: 'warning', icon: <WarningIcon /> },
  critical: { color: 'error', icon: <ErrorIcon /> },
} as const

/** Status is never color-only: icon + label. */
export function HealthChip({ index, help }: { index: number | null; help?: string }) {
  const { t } = useTranslation()
  const health = healthOf(index)
  if (null === health) {
    return <Chip size="small" variant="outlined" label={t('dashboard.noData')} />
  }
  const chip = <Chip size="small" color={STYLE[health].color} icon={STYLE[health].icon} label={`${index!.toFixed(2).replace('.', ',')} · ${t(`dashboard.status.${health}`)}`} />

  return help ? <Tooltip title={help}>{chip}</Tooltip> : chip
}
