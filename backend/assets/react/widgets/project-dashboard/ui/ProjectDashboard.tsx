import {useQuery} from '@tanstack/react-query';
import {
  alertText,
  dashboardKey,
  fetchDashboard,
  HealthBadge,
} from '@/entities/dashboard';
import {stageLegend, stageTone} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {formatDate, formatMoney, formatPercent} from '@/shared/lib/format';
import {
  Alert,
  DataTable,
  EmptyState,
  ErrorState,
  Loading,
  ProgressBar,
  Row,
  RowLegend,
  Stat,
  TabIntro,
} from '@/shared/ui';
import {MonthlyChart, StageChart} from './charts';

/** "1 oct – 15 dic", "1 oct – …" while it runs, "—" when it has neither date. */
function span(from?: string | null, to?: string | null): string {
  if (!from && !to) {
    return '—';
  }

  // A stage under way has no end yet: "desde 30 ago 2026" rather than a dangling dash (QA-0015).
  if (from && !to) {
    return t('dashboard.since', {date: formatDate(from)});
  }

  return `${formatDate(from)} – ${to ? formatDate(to) : '…'}`;
}

/** The "Tablero" tab: is the project spending in line with its progress, is it on time, what needs attention. */
export function ProjectDashboard({projectId}: {projectId: number}) {
  const query = useQuery({
    queryKey: dashboardKey(projectId),
    queryFn: () => fetchDashboard(projectId),
  });

  if (query.error) {
    return (
      <ErrorState error={query.error} onRetry={() => void query.refetch()} />
    );
  }
  if (!query.data) {
    return <Loading />;
  }
  const d = query.data;
  const money = (amount: string) => formatMoney(amount, d.currency);

  return (
    <div className="settings-sections">
      <TabIntro>{t('dashboard.intro')}</TabIntro>
      {!d.budgetApproved && (
        <Alert kind="info">{t('dashboard.notApproved')}</Alert>
      )}

      <section className="card">
        <div className="stat-grid">
          <Stat
            label={t('dashboard.spent')}
            value={money(d.totals.spent)}
            money
          >
            <span className="small muted">
              {t('dashboard.ofBudget', {
                percent: formatPercent(d.executed),
                budget: money(d.totals.budget),
              })}
            </span>
          </Stat>
          <Stat
            label={t('dashboard.progress')}
            value={formatPercent(d.progress)}
            money
          >
            <ProgressBar
              value={d.progress}
              label={t('dashboard.progress')}
              showValue={false}
            />
            <span className="small muted">
              {t('dashboard.progressVsPlan', {
                percent: formatPercent(d.plannedProgress),
              })}
            </span>
          </Stat>
          <Stat label={t('dashboard.indices')} value={null}>
            <div className="index-row" title={t('dashboard.cpiHelp')}>
              <span className="small muted">{t('dashboard.cpi')}</span>
              <HealthBadge index={d.cpi} />
            </div>
            <div className="index-row" title={t('dashboard.spiHelp')}>
              <span className="small muted">{t('dashboard.spi')}</span>
              <HealthBadge index={d.spi} />
            </div>
          </Stat>
          <Stat
            label={t('dashboard.forecast')}
            value={
              d.forecastAtCompletion
                ? money(d.forecastAtCompletion)
                : t('dashboard.noData')
            }
            money
          >
            <span className="small muted">
              {d.varianceAtCompletion
                ? t('dashboard.variance', {
                    amount: money(d.varianceAtCompletion),
                  })
                : t('dashboard.forecastHelp')}
            </span>
          </Stat>
        </div>
      </section>

      <section className="card">
        <div className="card-header">
          <h2>{t('dashboard.alertsTitle')}</h2>
        </div>
        {d.alerts.length === 0 ? (
          <p className="muted small">{t('dashboard.noAlerts')}</p>
        ) : (
          d.alerts.map((a, i) => (
            <Alert
              key={i}
              kind={
                a.level === 'error'
                  ? 'error'
                  : a.level === 'warning'
                    ? 'warning'
                    : 'info'
              }
            >
              {alertText(a)}
            </Alert>
          ))
        )}
      </section>

      <div className="chart-grid">
        {d.stages.length > 0 && <StageChart data={d} />}
        <MonthlyChart data={d} />
      </div>

      <section className="card">
        <div className="card-header">
          <h2>{t('dashboard.stagesTitle')}</h2>
        </div>
        {d.stages.length > 0 && <RowLegend statuses={stageLegend()} />}
        <DataTable
          empty={<EmptyState>{t('dashboard.noStages')}</EmptyState>}
          columns={[
            t('finance.stage'),
            t('dashboard.planned'),
            t('dashboard.actual'),
            t('dashboard.progress'),
            t('dashboard.executed'),
            t('dashboard.cpi'),
          ]}
          rows={d.stages}
          actions={false}
          renderRow={(s) => (
            <Row
              key={s.id}
              status={stageTone(s.status)}
              label={t(`plan.stageStatus.${s.status}`)}
            >
              <td>
                <strong>{s.name}</strong>
                {s.delayed && (
                  <div className="small">{t('dashboard.delayed')}</div>
                )}
              </td>
              <td>{span(s.plannedStart, s.plannedEnd)}</td>
              <td>{span(s.actualStart, s.actualEnd)}</td>
              <td className="num">
                {formatPercent(s.progress)}
                <div className="small muted">
                  {t('dashboard.plannedShort', {
                    percent: formatPercent(s.plannedProgress),
                  })}
                </div>
              </td>
              <td className="num">{formatPercent(s.executed)}</td>
              <td>
                <HealthBadge index={s.cpi} />
              </td>
            </Row>
          )}
        />
      </section>
    </div>
  );
}
