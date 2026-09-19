import { Box, Button, Paper, Stack, Typography } from '@mui/material'
import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'

export interface LegendItem {
  label: string
  color: string
}

/**
 * Chart frame: title, hint, HTML legend (text stays in text colors, the swatch carries
 * identity) and a chart/table toggle so the data is never color-only.
 */
export function ChartCard({ title, hint, legend, chart, table }: { title: string; hint?: string; legend: LegendItem[]; chart: ReactNode; table: ReactNode }) {
  const { t } = useTranslation()
  const [showTable, setShowTable] = useState(false)

  return (
    <Paper sx={{ p: 2, minWidth: 0 }}>
      <Stack direction="row" spacing={1} sx={{ alignItems: 'flex-start', mb: 1 }}>
        <Box sx={{ flexGrow: 1 }}>
          <Typography variant="subtitle1">{title}</Typography>
          {hint && (
            <Typography variant="caption" color="text.secondary">
              {hint}
            </Typography>
          )}
        </Box>
        <Button size="small" onClick={() => setShowTable(!showTable)}>
          {showTable ? t('dashboard.showChart') : t('dashboard.showTable')}
        </Button>
      </Stack>
      {legend.length > 1 && (
        <Stack direction="row" spacing={2} sx={{ mb: 1, flexWrap: 'wrap' }} component="ul" aria-label="Leyenda" style={{ listStyle: 'none', padding: 0, margin: '0 0 8px' }}>
          {legend.map((item) => (
            <Stack key={item.label} component="li" direction="row" spacing={0.75} sx={{ alignItems: 'center' }}>
              <Box sx={{ width: 10, height: 10, borderRadius: '2px', bgcolor: item.color }} />
              <Typography variant="caption" color="text.secondary">
                {item.label}
              </Typography>
            </Stack>
          ))}
        </Stack>
      )}
      {showTable ? <Box sx={{ overflowX: 'auto' }}>{table}</Box> : chart}
    </Paper>
  )
}

/** Tooltip body shared by the charts. */
export function ChartTooltip({ title, rows }: { title: string; rows: { label: string; value: string; color: string }[] }) {
  return (
    <Paper elevation={3} variant="elevation" sx={{ p: 1.25, minWidth: 180 }}>
      <Typography variant="caption" sx={{ fontWeight: 700, display: 'block', mb: 0.5 }}>
        {title}
      </Typography>
      {rows.map((row) => (
        <Stack key={row.label} direction="row" spacing={1} sx={{ alignItems: 'center' }}>
          <Box sx={{ width: 8, height: 8, borderRadius: '2px', bgcolor: row.color, flexShrink: 0 }} />
          <Typography variant="caption" color="text.secondary" sx={{ flexGrow: 1 }}>
            {row.label}
          </Typography>
          <Typography variant="caption" sx={{ fontWeight: 600 }}>
            {row.value}
          </Typography>
        </Stack>
      ))}
    </Paper>
  )
}
