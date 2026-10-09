import {useQuery} from '@tanstack/react-query';
import {Link} from 'react-router';
import {fetchPortfolio, HealthBadge, PORTFOLIO_KEY} from '@/entities/dashboard';
import {projectStatusLabel, projectTone} from '@/entities/project';
import {t} from '@/shared/i18n';
import {formatMoney, formatPercent} from '@/shared/lib/format';
import {
  Badge,
  EmptyState,
  ErrorState,
  Loading,
  PageHeader,
  ProgressBar,
} from '@/shared/ui';

/** Every project whose money the person sees, at a glance: budget, spending, progress, health. */
export function Portfolio() {
  const rows = useQuery({queryKey: PORTFOLIO_KEY, queryFn: fetchPortfolio});

  return (
    <>
      <PageHeader
        title={t('dashboard.title')}
        subtitle={t('dashboard.subtitle')}
      />
      {rows.error ? (
        <ErrorState error={rows.error} onRetry={() => void rows.refetch()} />
      ) : !rows.data ? (
        <Loading />
      ) : rows.data.length === 0 ? (
        <EmptyState>{t('dashboard.empty')}</EmptyState>
      ) : (
        <div className="portfolio-grid">
          {rows.data.map((p) => (
            <Link
              key={p.id}
              to={`/projects/${p.id}?tab=dashboard`}
              className="card portfolio-card"
            >
              <div className="card-header portfolio-card-header">
                <h2 className="cell-clamp" title={p.name}>
                  {p.name}
                </h2>
                <Badge value={projectTone(p.status)}>
                  {projectStatusLabel(p.status)}
                </Badge>
              </div>
              <dl className="definitions">
                <div>
                  <dt>{t('dashboard.budget')}</dt>
                  <dd>{formatMoney(p.budget, p.currency)}</dd>
                </div>
                <div>
                  <dt>{t('dashboard.spent')}</dt>
                  <dd>
                    {formatMoney(p.spent, p.currency)} ·{' '}
                    {formatPercent(p.executed)}
                  </dd>
                </div>
              </dl>
              <ProgressBar value={p.progress} label={t('dashboard.progress')} />
              <p className="small muted">
                {t('dashboard.progressVsPlan', {
                  percent: formatPercent(p.plannedProgress),
                })}
              </p>
              {p.cpi === null && p.spi === null && p.alerts === 0 ? (
                // Nothing to read yet: one sentence rather than two "Sin datos" chips (QA-0015).
                <p className="small muted">{t('dashboard.noIndicesYet')}</p>
              ) : (
                <div className="portfolio-health">
                  <span className="small muted">CPI</span>
                  <HealthBadge index={p.cpi} />
                  <span className="small muted">SPI</span>
                  <HealthBadge index={p.spi} />
                  {p.alerts > 0 && (
                    <Badge value="health_warning">
                      {t('dashboard.alertCount', {count: p.alerts})}
                    </Badge>
                  )}
                </div>
              )}
            </Link>
          ))}
        </div>
      )}
    </>
  );
}
