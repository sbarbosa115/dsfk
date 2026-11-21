import type {ReactNode} from 'react';
import {formatPercent} from '@/shared/lib/format';

/** A share done, in basis points (10000 = 100 %): the bar and the percentage in words. */
export function ProgressBar({
  value,
  label,
  showValue = true,
  marker,
  markerLabel,
}: {
  value: number;
  label?: string;
  /** Off where the percentage is already written next to it (a tile). */
  showValue?: boolean;
  /** A tick on the track, in basis points: where the bar should be by now (the share of the time gone). */
  marker?: number | null;
  /** The tick in words, as its tooltip; the page says it in text too. */
  markerLabel?: string;
}) {
  const percent = Math.max(0, Math.min(100, value / 100));
  const tick = marker == null ? null : Math.max(0, Math.min(100, marker / 100));

  return (
    <div className="progress">
      <div
        className="progress-track"
        role="progressbar"
        aria-label={label}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-valuenow={Math.round(percent)}
      >
        <div className="progress-fill" style={{width: `${percent}%`}} />
        {tick !== null && (
          <div
            className="progress-marker"
            style={{left: `${tick}%`}}
            title={markerLabel}
            aria-hidden="true"
          />
        )}
      </div>
      {showValue && (
        <span className="progress-value">{formatPercent(value)}</span>
      )}
    </div>
  );
}

/** A figure in a tile: what it is, and its value (an amount, a percentage, a count). */
export function Stat({
  label,
  value,
  money = false,
  children,
}: {
  label: string;
  value: ReactNode;
  money?: boolean;
  children?: ReactNode;
}) {
  return (
    <div className="stat">
      <span className="stat-label">{label}</span>
      <span className={`stat-value ${money ? 'stat-value-money' : ''}`}>
        {value}
      </span>
      {children}
    </div>
  );
}
