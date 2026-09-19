import { Box, LinearProgress, Typography } from '@mui/material'
import { formatPercent } from '../lib/format'

/** Progress in basis points (10000 = 100%) with its label. */
export function ProgressBar({ value, label }: { value: number; label?: string }) {
  return (
    <Box sx={{ minWidth: 120 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
        {label && (
          <Typography variant="caption" color="text.secondary">
            {label}
          </Typography>
        )}
        <Typography variant="caption" sx={{ fontWeight: 600, ml: 'auto' }}>
          {formatPercent(value)}
        </Typography>
      </Box>
      <LinearProgress variant="determinate" value={Math.min(100, value / 100)} sx={{ height: 6, borderRadius: 3 }} />
    </Box>
  )
}
