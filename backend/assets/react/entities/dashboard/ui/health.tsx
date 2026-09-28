import {t} from '@/shared/i18n';
import {formatDate, formatPercent} from '@/shared/lib/format';
import {Badge} from '@/shared/ui';
import type {DashboardAlert} from '../api/dashboardApi';

export type Health = 'good' | 'warning' | 'critical';

/** CPI and SPI: 1 or more is on track, 0.9 to 1 needs attention, below 0.9 is critical. */
export function healthOf(index: number | null | undefined): Health | null {
  if (index === null || index === undefined) {
    return null;
  }

  return index >= 1 ? 'good' : index >= 0.9 ? 'warning' : 'critical';
}

/** "0,80 · Crítico": the index and its reading in words, never the colour alone. */
export function HealthBadge({index}: {index: number | null | undefined}) {
  const health = healthOf(index);
  if (health === null) {
    return <Badge value="health_none">{t('dashboard.noData')}</Badge>;
  }

  return (
    <Badge value={`health_${health}`}>
      {index!.toFixed(2).replace('.', ',')} · {t(`dashboard.health.${health}`)}
    </Badge>
  );
}

export function alertText(alert: DashboardAlert): string {
  return t(`dashboard.alerts.${alert.code}`, {
    stage: alert.stage ?? '',
    executed: formatPercent(alert.executed ?? 0),
    plannedEnd: formatDate(alert.plannedEnd),
    count: alert.count ?? 0,
  });
}
