import WarningIcon from '@mui/icons-material/ReportProblemOutlined'
import { Alert, Box, Card, CardActionArea, CardContent, Chip, Stack, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { api } from '../api/client'
import type { PortfolioRow } from '../api/types'
import { ProgressBar } from '../components/ProgressBar'
import { PageHeader } from '../layout/PageHeader'
import { errorMessage } from '../lib/errors'
import { formatMoney, formatPercent } from '../lib/format'
import { HealthChip } from './dashboard/Indicators'
import { ProjectStatusChip } from './ProjectStatusChip'

export function DashboardPage() {
  const { t } = useTranslation()
  const rows = useQuery({ queryKey: ['portfolio'], queryFn: () => api<PortfolioRow[]>('/dashboard') })

  return (
    <>
      <PageHeader title={t('dashboard.title')} />
      {rows.isPending && <Typography color="text.secondary">{t('common.loading')}</Typography>}
      {rows.error && <Alert severity="error">{errorMessage(t, rows.error)}</Alert>}
      {rows.data?.length === 0 && <Alert severity="info">{t('dashboard.empty')}</Alert>}

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))' }}>
        {rows.data?.map((p) => (
          <Card key={p.id}>
            <CardActionArea component={Link} to={`/projects/${p.id}?tab=dashboard`} sx={{ height: '100%' }}>
              <CardContent>
                <Stack direction="row" spacing={1} sx={{ justifyContent: 'space-between', mb: 1.5 }}>
                  <Typography variant="h6" component="h2">
                    {p.name}
                  </Typography>
                  <ProjectStatusChip status={p.status} />
                </Stack>

                <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 1.5, mb: 1.5 }}>
                  <Figure label={t('dashboard.budget')} value={formatMoney(p.budget, p.currency)} />
                  <Figure label={t('dashboard.spent')} value={`${formatMoney(p.spent, p.currency)} · ${formatPercent(p.executed)}`} />
                </Box>
                <ProgressBar value={p.progress} label={t('dashboard.progress')} />
                <Typography variant="caption" color="text.secondary">
                  {t('dashboard.progressVsPlan', { percent: formatPercent(p.plannedProgress) })}
                </Typography>

                <Stack direction="row" spacing={1} sx={{ mt: 1.5, flexWrap: 'wrap', gap: 1, alignItems: 'center' }}>
                  <Typography variant="caption" color="text.secondary">
                    CPI
                  </Typography>
                  <HealthChip index={p.cpi} />
                  <Typography variant="caption" color="text.secondary">
                    SPI
                  </Typography>
                  <HealthChip index={p.spi} />
                  {p.alerts > 0 && <Chip size="small" color="warning" icon={<WarningIcon />} label={t('dashboard.alertCount', { count: p.alerts })} />}
                </Stack>
              </CardContent>
            </CardActionArea>
          </Card>
        ))}
      </Box>
    </>
  )
}

function Figure({ label, value }: { label: string; value: string }) {
  return (
    <Box>
      <Typography variant="caption" color="text.secondary">
        {label}
      </Typography>
      <Typography variant="body2" sx={{ fontWeight: 600 }}>
        {value}
      </Typography>
    </Box>
  )
}
