/**
 * Chart colors (validated with the dataviz palette checker against the white card surface).
 * Each entity keeps its color across charts:
 *   progress = blue (slot 1), planned = orange (slot 2), spent = aqua (slot 3), deposited = violet (slot 7).
 * Aqua is below 3:1 contrast, so every chart also offers a table view.
 */
export const SERIES = {
  progress: '#2a78d6',
  planned: '#eb6834',
  spent: '#1baf7a',
  deposited: '#4a3aa7',
} as const

export const CHART = {
  surface: '#ffffff',
  grid: '#e7e8ea',
  axis: '#6b7280',
  text: '#1f2933',
  reference: '#9aa1ab',
}
