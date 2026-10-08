import {describe, expect, it} from 'vitest';
import {
  formatDate,
  formatDateRange,
  formatDateTime,
  formatMoney,
  formatPercent,
  formatAmountForInput,
  parseAmountInput,
  toDateInput,
} from './format';

// Intl output uses non-breaking spaces; normalise them for readable assertions.
const plain = (value: string) => value.replace(/\s/g, ' ');

describe('formatMoney', () => {
  it('formats COP without decimals and with dot thousands separators', () => {
    expect(plain(formatMoney('1500000', 'COP'))).toBe('$ 1.500.000');
  });

  it('shows cents only when the amount has them, so a price is never rounded away', () => {
    expect(plain(formatMoney('35000.50', 'COP'))).toBe('$ 35.000,50');
    expect(plain(formatMoney('437506.00', 'COP'))).toBe('$ 437.506');
  });

  it('keeps two decimals for other currencies', () => {
    expect(plain(formatMoney('1234.5', 'USD'))).toContain('1.234,50');
  });
});

describe('dates', () => {
  it('uses the date part of the API value, without timezone shifts', () => {
    expect(formatDate('2026-10-01T00:00:00-05:00')).toBe('1 de oct de 2026');
    expect(toDateInput('2026-10-01T00:00:00-05:00')).toBe('2026-10-01');
  });

  it('renders a dash for empty dates', () => {
    expect(formatDate(null)).toBe('—');
    expect(toDateInput(null)).toBe('');
  });

  it('writes a range once, with an ellipsis for the missing end', () => {
    expect(formatDateRange('2026-10-01', '2027-06-30')).toBe(
      '1 de oct de 2026 – 30 de jun de 2027',
    );
    expect(formatDateRange('2026-10-01', null)).toBe('1 de oct de 2026 – …');
    expect(formatDateRange(null, '2027-06-30')).toBe('… – 30 de jun de 2027');
    expect(formatDateRange(null, null)).toBe('—');
  });
});

describe('formatPercent', () => {
  it('reads basis points (10000 = 100 %)', () => {
    expect(plain(formatPercent(3240))).toBe('32,4%');
  });
});

describe('amount inputs', () => {
  it('reads Colombian notation into the API decimal string', () => {
    expect(parseAmountInput('1.500.000')).toBe('1500000');
    expect(parseAmountInput('1.500.000,50')).toBe('1500000.50');
    expect(parseAmountInput('$ 25.000')).toBe('25000');
    expect(parseAmountInput('')).toBeNull();
    expect(parseAmountInput('12,345')).toBeUndefined();
  });

  it('writes an API amount back in Colombian notation', () => {
    expect(formatAmountForInput('2500000.50')).toBe('2.500.000,50');
    expect(formatAmountForInput('2500000.00')).toBe('2.500.000');
  });
});

describe('formatDateTime', () => {
  // Intl separates the time and "p. m." with non-breaking spaces.
  const plain = (value: string) => value.replace(/\s/g, ' ');

  it('writes the date the way every other date reads, and the time in Bogotá wherever the browser is', () => {
    // 02:23 UTC on the 8th is 9:23 p. m. on the 7th in Bogotá (UTC-5).
    expect(plain(formatDateTime('2026-10-08T02:23:00+00:00'))).toBe(
      '7 de oct de 2026, 9:23 p. m.',
    );
    expect(plain(formatDateTime('2026-10-15T10:00:00-05:00'))).toBe(
      '15 de oct de 2026, 10:00 a. m.',
    );
    expect(formatDateTime(null)).toBe('—');
  });
});
