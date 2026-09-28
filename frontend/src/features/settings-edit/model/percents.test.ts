import {describe, expect, it} from 'vitest';
import {parsePercents} from './percents';

describe('parsePercents', () => {
  it('reads a comma separated list and skips empty parts', () => {
    expect(parsePercents('80, 100')).toEqual([80, 100]);
    expect(parsePercents(' 50,,90 ')).toEqual([50, 90]);
  });

  it('keeps invalid entries so the API can refuse them field by field', () => {
    expect(parsePercents('80, abc')).toEqual([80, NaN]);
  });
});
