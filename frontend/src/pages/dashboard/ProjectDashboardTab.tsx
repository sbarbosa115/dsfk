import ErrorIcon from '@mui/icons-material/ErrorOutlined'
import InfoIcon from '@mui/icons-material/InfoOutlined'
import WarningIcon from '@mui/icons-material/ReportProblemOutlined'
import {
  Alert,
  Box,
  Chip,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { api } from '../../api/client'
import type { DashboardAlert, ProjectDashboard } from '../../api/types'
import { ProgressBar } from '../../components/ProgressBar'
import { errorMessage } from '../../lib/errors'
import { formatDate, formatMoney, formatPercent } from '../../lib/format'
import { MonthlyChart, StageProgressChart } from './charts'
import { HealthChip } from './Indicators'

const ALERT_ICONS = { error: <ErrorIcon color="error" />, warning: <WarningIcon color="warning" />, info: <InfoIcon color="info" /> }

export function ProjectDashboardTab({ projectId }: { projectId: string }) {
  const { t } = useTranslation()
  const query = useQuery({ queryKey: ['dashboard', projectId], queryFn: () => api<ProjectDashboard>(`/projects/${projectId}/dashboard`) })

  if (query.error) {
    return <Alert severity="error">{errorMessage(t, query.error)}</Alert>
  }
  if (!query.data) {
    return <Typography color="text.secondary">{t('common.loading')}</Typography>
  }
  const d = query.data
  const money = (v: string) => formatMoney(v, d.currency)

  return (
    <Stack spacing={2}>
      {!d.budgetApproved && <Alert severity="info">{t('dashboard.notApproved')}</Alert>}

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr', sm: '1fr 1fr', lg: 'repeat(4, 1fr)' } }}>
        <Tile label={t('dashboard.spent')} value={money(d.totals.spent)}>
          <Typography variant="caption" color="text.secondary">
            {t('dashboard.ofBudget', { percent: formatPercent(d.executed) })} · {t('dashboard.budget')} {money(d.totals.budget)}
          </Typography>
        </Tile>
        <Tile label={t('dashboard.progress')} value={formatPercent(d.progress)}>
          <ProgressBar value={d.progress} />
          <Typography variant="caption" color="text.secondary">
            {t('dashboard.progressVsPlan', { percent: formatPercent(d.plannedProgress) })}
          </Typography>
        </Tile>
        <Tile label={t('dashboard.cpi')} help={t('dashboard.cpiHelp')}>
          <HealthChip index={d.cpi} help={t('dashboard.cpiHelp')} />
          <Box sx={{ mt: 1 }}>
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
              {t('dashboard.spi')}
            </Typography>
            <HealthChip index={d.spi} help={t('dashboard.spiHelp')} />
          </Box>
        </Tile>
        <Tile label={t('dashboard.forecast')} value={d.forecastAtCompletion ? money(d.forecastAtCompletion) : t('dashboard.noData')} help={t('dashboard.forecastHelp')}>
          {d.varianceAtCompletion && (
            <Typography variant="caption" color={Number(d.varianceAtCompletion) < 0 ? 'error' : 'text.secondary'}>
              {t('dashboard.variance', { amount: money(d.varianceAtCompletion) })}
            </Typography>
          )}
        </Tile>
      </Box>

      <Paper sx={{ p: 2 }}>
        <Typography variant="subtitle1">{t('dashboard.alertsTitle')}</Typography>
        {d.alerts.length === 0 ? (
          <Typography variant="body2" color="text.secondary">
            {t('dashboard.noAlerts')}
          </Typography>
        ) : (
          <List dense disablePadding>
            {d.alerts.map((a, i) => (
              <ListItem key={i} disableGutters>
                <ListItemIcon sx={{ minWidth: 36 }}>{ALERT_ICONS[a.level]}</ListItemIcon>
                <ListItemText primary={alertText(t, a)} />
              </ListItem>
            ))}
          </List>
        )}
      </Paper>

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr', lg: '1fr 1fr' } }}>
        {d.stages.length > 0 && <StageProgressChart data={d} />}
        <MonthlyChart data={d} />
      </Box>

      <Paper>
        <Typography variant="subtitle1" sx={{ p: 2, pb: 1 }}>
          {t('dashboard.stagesTitle')}
        </Typography>
        <TableContainer>
          <Table size="small" sx={{ minWidth: 760 }}>
            <TableHead>
              <TableRow>
                <TableCell>{t('dashboard.stage')}</TableCell>
                <TableCell>{t('dashboard.planned')}</TableCell>
                <TableCell>{t('dashboard.actual')}</TableCell>
                <TableCell align="right">{t('dashboard.progress')}</TableCell>
                <TableCell align="right">{t('dashboard.executed')}</TableCell>
                <TableCell>CPI</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {d.stages.map((s) => (
                <TableRow key={s.id}>
                  <TableCell>
                    <Stack direction="row" spacing={1} sx={{ alignItems: 'center' }}>
                      <span>{s.name}</span>
                      {s.delayed && <Chip size="small" color="warning" icon={<WarningIcon />} label={t('dashboard.delayed')} />}
                    </Stack>
                    <Typography variant="caption" color="text.secondary">
                      {t(`stageStatus.${s.status}`)}
                    </Typography>
                  </TableCell>
                  <TableCell sx={{ whiteSpace: 'nowrap' }}>
                    {formatDate(s.plannedStart)} – {formatDate(s.plannedEnd)}
                  </TableCell>
                  <TableCell sx={{ whiteSpace: 'nowrap' }}>
                    {formatDate(s.actualStart)} – {formatDate(s.actualEnd)}
                  </TableCell>
                  <TableCell align="right">{formatPercent(s.progress)}</TableCell>
                  <TableCell align="right">{formatPercent(s.executed)}</TableCell>
                  <TableCell>
                    <HealthChip index={s.cpi} />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>
    </Stack>
  )
}

function alertText(t: (key: string, params?: Record<string, unknown>) => string, a: DashboardAlert): string {
  const params: Record<string, unknown> = { ...a.params }
  if (typeof a.params.executed === 'number') {
    params.executed = formatPercent(a.params.executed)
  }
  if (typeof a.params.plannedEnd === 'string') {
    params.plannedEnd = formatDate(a.params.plannedEnd)
  }

  return t(`dashboard.alerts.${a.code}`, params)
}

function Tile({ label, value, help, children }: { label: string; value?: string; help?: string; children?: ReactNode }) {
  return (
    <Paper sx={{ p: 2 }} title={help}>
      <Typography variant="caption" color="text.secondary">
        {label}
      </Typography>
      {value && (
        <Typography variant="h5" sx={{ fontWeight: 700, mb: 0.5 }}>
          {value}
        </Typography>
      )}
      {children}
    </Paper>
  )
}
