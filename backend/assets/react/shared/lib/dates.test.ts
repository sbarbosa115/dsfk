import {describe, expect, it} from 'vitest';
import {partsOrder, toIso, toText} from './dates';

describe('dates', () => {
  const order = partsOrder('es-CO');

  it('writes and reads dates day first in Colombia', () => {
    expect(order).toEqual(['day', 'month', 'year']);
    expect(toText('2026-10-01', order)).toBe('01/10/2026');
    expect(toIso('1/10/2026', order)).toBe('2026-10-01');
  });

  it('refuses dates that do not exist instead of rolling them over', () => {
    expect(toIso('31/02/2026', order)).toBeNull();
    expect(toIso('1/10', order)).toBeNull();
    expect(toIso('', order)).toBe('');
  });
});
