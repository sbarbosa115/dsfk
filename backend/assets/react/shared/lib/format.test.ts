import {describe, expect, it} from 'vitest';
import {
  formatDate,
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
