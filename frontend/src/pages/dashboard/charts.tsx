import { Table, TableBody, TableCell, TableHead, TableRow } from '@mui/material'
import { useTranslation } from 'react-i18next'
import { Bar, BarChart, CartesianGrid, ReferenceLine, ResponsiveContainer, Tooltip, XAxis, YAxis, type TooltipContentProps } from 'recharts'
import type { ProjectDashboard } from '../../api/types'
import { ChartCard, ChartTooltip } from '../../components/charts/ChartCard'
import { CHART, SERIES } from '../../components/charts/palette'
import { formatCompactMoney, formatMoney, formatMonth, formatPercent } from '../../lib/format'

const axisTick = { fill: CHART.axis, fontSize: 12 }

/** Physical progress (real and planned) against budget execution, per stage, all in % of the stage. */
export function StageProgressChart({ data }: { data: ProjectDashboard }) {
  const { t } = useTranslation()
  const series = [
    { key: 'progress', label: t('dashboard.progress'), color: SERIES.progress },
    { key: 'plannedProgress', label: t('dashboard.plannedProgress'), color: SERIES.planned },
    { key: 'executed', label: t('dashboard.executed'), color: SERIES.spent },
  ] as const
  const rows = data.stages.map((s) => ({ name: s.name, progress: s.progress / 100, plannedProgress: s.plannedProgress / 100, executed: s.executed / 100 }))
  const max = Math.max(100, ...rows.flatMap((r) => [r.progress, r.plannedProgress, r.executed]))

  const tooltip = ({ active, payload, label }: TooltipContentProps) =>
    active && payload?.length ? (
      <ChartTooltip
        title={String(label)}
        rows={series.map((s) => ({ label: s.label, color: s.color, value: formatPercent(Number(payload.find((p) => p.dataKey === s.key)?.value ?? 0) * 100) }))}
      />
    ) : null

  return (
    <ChartCard
      title={t('dashboard.stageChart')}
      hint={t('dashboard.stageChartHint')}
      legend={series.map((s) => ({ label: s.label, color: s.color }))}
      chart={
        <ResponsiveContainer width="100%" height={Math.max(160, rows.length * 64 + 40)}>
          <BarChart data={rows} layout="vertical" margin={{ top: 4, right: 16, bottom: 4, left: 8 }} barGap={2} barCategoryGap="22%">
            <CartesianGrid horizontal={false} stroke={CHART.grid} />
            <XAxis type="number" domain={[0, Math.ceil(max / 10) * 10]} tickFormatter={(v: number) => `${v}%`} tick={axisTick} axisLine={false} tickLine={false} />
            <YAxis type="category" dataKey="name" width={120} tick={axisTick} axisLine={false} tickLine={false} />
            <ReferenceLine x={100} stroke={CHART.reference} strokeDasharray="4 4" />
            <Tooltip content={tooltip} cursor={{ fill: 'rgba(0,0,0,0.04)' }} />
            {series.map((s) => (
              <Bar key={s.key} dataKey={s.key} name={s.label} fill={s.color} barSize={8} radius={[0, 4, 4, 0]} stroke={CHART.surface} strokeWidth={1} isAnimationActive={false} />
            ))}
          </BarChart>
        </ResponsiveContainer>
      }
      table={
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>{t('dashboard.stage')}</TableCell>
              {series.map((s) => (
                <TableCell key={s.key} align="right">
                  {s.label}
                </TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {rows.map((r) => (
              <TableRow key={r.name}>
                <TableCell>{r.name}</TableCell>
                {series.map((s) => (
                  <TableCell key={s.key} align="right">
                    {formatPercent(r[s.key] * 100)}
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      }
    />
  )
}

/** Money in (deposits) and money spent per month. */
export function MonthlyChart({ data }: { data: ProjectDashboard }) {
  const { t } = useTranslation()
  const currency = data.currency
  const series = [
    { key: 'deposited', label: t('dashboard.deposited'), color: SERIES.deposited },
    { key: 'spent', label: t('dashboard.spent'), color: SERIES.spent },
  ] as const
  const rows = data.monthly.map((m) => ({ month: formatMonth(m.month), deposited: Number(m.deposited), spent: Number(m.spent) }))

  const tooltip = ({ active, payload, label }: TooltipContentProps) =>
    active && payload?.length ? (
      <ChartTooltip
        title={String(label)}
        rows={series.map((s) => ({ label: s.label, color: s.color, value: formatMoney(Number(payload.find((p) => p.dataKey === s.key)?.value ?? 0), currency) }))}
      />
    ) : null

  return (
    <ChartCard
      title={t('dashboard.monthlyChart')}
      legend={series.map((s) => ({ label: s.label, color: s.color }))}
      chart={
        <ResponsiveContainer width="100%" height={260}>
          <BarChart data={rows} margin={{ top: 8, right: 8, bottom: 4, left: 8 }} barGap={2} barCategoryGap="28%">
            <CartesianGrid vertical={false} stroke={CHART.grid} />
            <XAxis dataKey="month" tick={axisTick} axisLine={false} tickLine={false} interval="preserveStartEnd" minTickGap={12} />
            <YAxis tickFormatter={(v: number) => formatCompactMoney(v, currency)} tick={axisTick} axisLine={false} tickLine={false} width={72} />
            <Tooltip content={tooltip} cursor={{ fill: 'rgba(0,0,0,0.04)' }} />
            {series.map((s) => (
              <Bar key={s.key} dataKey={s.key} name={s.label} fill={s.color} maxBarSize={14} radius={[4, 4, 0, 0]} stroke={CHART.surface} strokeWidth={1} isAnimationActive={false} />
            ))}
          </BarChart>
        </ResponsiveContainer>
      }
      table={
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>{t('dashboard.month')}</TableCell>
              {series.map((s) => (
                <TableCell key={s.key} align="right">
                  {s.label}
                </TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {rows.map((r) => (
              <TableRow key={r.month}>
                <TableCell>{r.month}</TableCell>
                <TableCell align="right">{formatMoney(r.deposited, currency)}</TableCell>
                <TableCell align="right">{formatMoney(r.spent, currency)}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      }
    />
  )
}
