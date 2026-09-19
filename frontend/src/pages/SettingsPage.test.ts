import { describe, expect, it } from 'vitest'
import { parsePercents } from './SettingsPage'

describe('parsePercents', () => {
  it('parses a comma separated list', () => {
    expect(parsePercents('80, 100')).toEqual([80, 100])
    expect(parsePercents(' 50,,90 ')).toEqual([50, 90])
  })
})
