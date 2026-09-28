/** "80, 100" → [80, 100]. Invalid entries become NaN so the API refuses them field by field. */
export function parsePercents(value: string): number[] {
  return value
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean)
    .map(Number);
}
