import {useState, type ReactNode} from 'react';
import {t} from '@/shared/i18n';
import {Button} from './ui';

/**
 * A chart with its title, its legend and the same figures as a table: the chart is for seeing the shape, the
 * table for reading exact values (and for screen readers). Series colours come from CSS classes
 * (`chart-series-*`), so both themes apply.
 */
export function ChartCard({
  title,
  hint,
  legend,
  chart,
  table,
}: {
  title: string;
  hint?: string;
  legend: ReadonlyArray<{label: string; series: string}>;
  chart: ReactNode;
  table: ReactNode;
}) {
  const [asTable, setAsTable] = useState(false);

  return (
    <section className="card chart-card">
      <div className="card-header">
        <div>
          <h2>{title}</h2>
          {hint && <p className="small muted">{hint}</p>}
        </div>
        <Button variant="ghost" onClick={() => setAsTable((v) => !v)}>
          {asTable ? t('charts.showChart') : t('charts.showTable')}
        </Button>
      </div>
      {asTable ? (
        table
      ) : (
        <>
          <ul className="chart-legend">
            {legend.map((item) => (
              <li key={item.series}>
                <span
                  className={`chart-swatch chart-series-${item.series}`}
                  aria-hidden="true"
                />
                {item.label}
              </li>
            ))}
          </ul>
          <div className="chart-body" aria-hidden="true">
            {chart}
          </div>
        </>
      )}
    </section>
  );
}
