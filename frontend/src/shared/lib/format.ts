const LOCALE = 'es-CO';

/** An amount in major units, e.g. formatMoney('1500000', 'COP') → "$ 1.500.000". */
export function formatMoney(amount: string | number, currency: string): string {
  const digits = currency === 'COP' ? 0 : 2;

  return new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency,
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  }).format(Number(amount));
}

/** Short money for chart axes: "$ 12,5 M", "$ 850 mil". */
export function formatCompactMoney(
  amount: string | number,
  currency: string,
): string {
  return new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency,
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(amount));
}

function localDate(iso: string): Date {
  const [year = 1970, month = 1, day = 1] = iso
    .slice(0, 10)
    .split('-')
    .map(Number);

  return new Date(year, month - 1, day);
}

/**
 * The date part of an API date without timezone shifts ("2026-10-01T00:00:00-05:00" → "1 de oct de 2026").
 */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) {
    return '—';
  }

  return new Intl.DateTimeFormat(LOCALE, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(localDate(iso));
}

/** Date and time: "1 de oct de 2026, 3:04 p. m.". */
export function formatDateTime(iso: string | null | undefined): string {
  if (!iso) {
    return '—';
  }

  return new Intl.DateTimeFormat(LOCALE, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(iso));
}

/** "2026-10-01T00:00:00-05:00" → "2026-10-01" for date inputs. */
export function toDateInput(iso: string | null | undefined): string {
  return iso ? iso.slice(0, 10) : '';
}

/** "2026-09" → "sep 2026". */
export function formatMonth(month: string): string {
  return new Intl.DateTimeFormat(LOCALE, {
    month: 'short',
    year: 'numeric',
  }).format(localDate(`${month}-01`));
}

/** Basis points (10000 = 100 %) → "32,4 %". */
export function formatPercent(basisPoints: number): string {
  return new Intl.NumberFormat(LOCALE, {
    style: 'percent',
    maximumFractionDigits: 1,
  }).format(basisPoints / 10000);
}

/** Decimal string quantity → "12,5". */
export function formatQuantity(quantity: string): string {
  return new Intl.NumberFormat(LOCALE, {maximumFractionDigits: 3}).format(
    Number(quantity),
  );
}

/** An index such as CPI or SPI → "0,95"; null → "—". */
export function formatIndex(value: number | null | undefined): string {
  if (value === null || value === undefined) {
    return '—';
  }

  return new Intl.NumberFormat(LOCALE, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
}

/** Local date as YYYY-MM-DD (for date inputs defaulting to today). */
export function today(): string {
  const d = new Date();

  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/**
 * Amounts are typed Colombian style ("1.500.000", "1.500.000,50") or plain ("1500000.50"); the API expects
 * "1500000.50".
 */
export function parseAmount(value: string): string {
  const v = value.replace(/[\s$]/g, '');
  if (v.includes(',') || /\.\d{3}(\.|$)/.test(v)) {
    return v.replace(/\./g, '').replace(',', '.');
  }

  return v;
}
