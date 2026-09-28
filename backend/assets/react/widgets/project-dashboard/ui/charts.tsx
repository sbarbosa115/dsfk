import {
  Bar,
  BarChart,
  CartesianGrid,
  ReferenceLine,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import type {ProjectDashboard} from '@/entities/dashboard';
import {t} from '@/shared/i18n';
import {
  formatCompactMoney,
  formatMoney,
  formatMonth,
  formatPercent,
} from '@/shared/lib/format';
import {ChartCard, DataTable} from '@/shared/ui';

/** Physical progress (real and planned) against budget spent, per stage, each in % of the stage. */
export function StageChart({data}: {data: ProjectDashboard}) {
  const series = [
    {key: 'progress', label: t('dashboard.progress'), series: 'progress'},
    {
      key: 'plannedProgress',
      label: t('dashboard.plannedProgress'),
      series: 'planned',
    },
    {key: 'executed', label: t('dashboard.executed'), series: 'spent'},
  ] as const;
  const rows = data.stages.map((s) => ({
    name: s.name,
    progress: s.progress / 100,
    plannedProgress: s.plannedProgress / 100,
    executed: s.executed / 100,
  }));
  const max = Math.max(
    100,
    ...rows.flatMap((r) => [r.progress, r.plannedProgress, r.executed]),
  );

  return (
    <ChartCard
      title={t('dashboard.stageChart')}
      hint={t('dashboard.stageChartHint')}
      legend={series}
      chart={
        <ResponsiveContainer
          width="100%"
          height={Math.max(160, rows.length * 64 + 40)}
        >
          <BarChart
            data={rows}
            layout="vertical"
            margin={{top: 4, right: 16, bottom: 4, left: 8}}
            barGap={2}
            barCategoryGap="22%"
          >
            <CartesianGrid horizontal={false} />
            <XAxis
              type="number"
              domain={[0, Math.ceil(max / 10) * 10]}
              tickFormatter={(v: number) => `${v}%`}
              axisLine={false}
              tickLine={false}
            />
            <YAxis
              type="category"
              dataKey="name"
              width={120}
              axisLine={false}
              tickLine={false}
            />
            <ReferenceLine
              x={100}
              className="chart-reference"
              strokeDasharray="4 4"
            />
            <Tooltip formatter={(v) => formatPercent(Number(v) * 100)} />
            {series.map((s) => (
              <Bar
                key={s.key}
                dataKey={s.key}
                name={s.label}
                className={`chart-series-${s.series}`}
                barSize={8}
                radius={[0, 4, 4, 0]}
                isAnimationActive={false}
              />
            ))}
          </BarChart>
        </ResponsiveContainer>
      }
      table={
        <DataTable
          columns={[t('finance.stage'), ...series.map((s) => s.label)]}
          rows={rows}
          actions={false}
          renderRow={(r) => (
            <tr key={r.name}>
              <td>{r.name}</td>
              {series.map((s) => (
                <td key={s.key} className="num">
                  {formatPercent(r[s.key] * 100)}
                </td>
              ))}
            </tr>
          )}
        />
      }
    />
  );
}

/** Money in (deposits) and money spent per month. */
export function MonthlyChart({data}: {data: ProjectDashboard}) {
  const currency = data.currency;
  const series = [
    {key: 'deposited', label: t('dashboard.deposited'), series: 'deposited'},
    {key: 'spent', label: t('dashboard.spent'), series: 'spent'},
  ] as const;
  const rows = data.monthly.map((m) => ({
    month: formatMonth(m.month),
    deposited: Number(m.deposited),
    spent: Number(m.spent),
  }));

  return (
    <ChartCard
      title={t('dashboard.monthlyChart')}
      legend={series}
      chart={
        <ResponsiveContainer width="100%" height={260}>
          <BarChart
            data={rows}
            margin={{top: 8, right: 8, bottom: 4, left: 8}}
            barGap={2}
            barCategoryGap="28%"
          >
            <CartesianGrid vertical={false} />
            <XAxis
              dataKey="month"
              axisLine={false}
              tickLine={false}
              interval="preserveStartEnd"
              minTickGap={12}
            />
            <YAxis
              tickFormatter={(v: number) => formatCompactMoney(v, currency)}
              axisLine={false}
              tickLine={false}
              width={72}
            />
            <Tooltip formatter={(v) => formatMoney(Number(v), currency)} />
            {series.map((s) => (
              <Bar
                key={s.key}
                dataKey={s.key}
                name={s.label}
                className={`chart-series-${s.series}`}
                maxBarSize={14}
                radius={[4, 4, 0, 0]}
                isAnimationActive={false}
              />
            ))}
          </BarChart>
        </ResponsiveContainer>
      }
      table={
        <DataTable
          columns={[t('dashboard.month'), ...series.map((s) => s.label)]}
          rows={rows}
          actions={false}
          renderRow={(r) => (
            <tr key={r.month}>
              <td>{r.month}</td>
              {series.map((s) => (
                <td key={s.key} className="num">
                  {formatMoney(r[s.key], currency)}
                </td>
              ))}
            </tr>
          )}
        />
      }
    />
  );
}
