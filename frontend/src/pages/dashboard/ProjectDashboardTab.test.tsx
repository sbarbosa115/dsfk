import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest'
import type { ProjectDashboard } from '../../api/types'
import { healthOf } from './Indicators'
import { ProjectDashboardTab } from './ProjectDashboardTab'

const dashboard: ProjectDashboard = {
  currency: 'COP',
  budgetApproved: true,
  totals: { budget: '1000000.00', contingency: '0.00', deposited: '1000000.00', spent: '600000.00', available: '400000.00', pettyCash: '0.00' },
  progress: 4000,
  plannedProgress: 5000,
  executed: 6000,
  earnedValue: '400000.00',
  plannedValue: '500000.00',
  cpi: 0.67,
  spi: 0.8,
  forecastAtCompletion: '1492537.31',
  varianceAtCompletion: '-492537.31',
  stages: [
    {
      id: 1, name: 'Obra', status: 'IN_PROGRESS', budget: '1000000.00', spent: '600000.00', executed: 6000, progress: 4000, plannedProgress: 5000,
      cpi: 0.67, spi: 0.8, plannedStart: '2026-08-01', plannedEnd: '2026-09-01', actualStart: '2026-08-03', actualEnd: null, delayed: true,
    },
  ],
  monthly: [{ month: '2026-09', deposited: '1000000.00', spent: '600000.00' }],
  alerts: [{ level: 'warning', code: 'stage_near_budget', params: { stage: 'Obra', executed: 8500 } }],
}

beforeAll(() => {
  // Recharts measures its container.
  globalThis.ResizeObserver ??= class {
    observe() {}
    unobserve() {}
    disconnect() {}
  } as unknown as typeof ResizeObserver
})
afterEach(() => vi.unstubAllGlobals())

describe('healthOf', () => {
  it('classifies performance indexes', () => {
    expect(healthOf(1.05)).toBe('good')
    expect(healthOf(0.95)).toBe('warning')
    expect(healthOf(0.7)).toBe('critical')
    expect(healthOf(null)).toBeNull()
  })
})

describe('ProjectDashboardTab', () => {
  it('shows indicators with labels, alerts and a table view of each chart', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => new Response(JSON.stringify(dashboard), { status: 200, headers: { 'Content-Type': 'application/json' } })))
    render(
      <QueryClientProvider client={new QueryClient()}>
        <ProjectDashboardTab projectId="1" />
      </QueryClientProvider>,
    )

    expect(await screen.findByText(/La etapa “Obra” va en 85\s?% de su presupuesto\./)).toBeInTheDocument()
    expect(screen.getAllByText('0,67 · Crítico').length).toBeGreaterThan(0)
    expect(screen.getByText('0,80 · Crítico')).toBeInTheDocument()
    expect(screen.getByText('Atrasada')).toBeInTheDocument()

    const stageChart = screen.getByText('Avance vs. ejecución por etapa').closest('.MuiPaper-root') as HTMLElement
    await userEvent.click(within(stageChart).getByRole('button', { name: 'Ver tabla' }))
    const table = within(stageChart).getByRole('table')
    expect(within(table).getByText('Obra')).toBeInTheDocument()
    expect(within(table).getAllByText(/^(40|50|60)\s?%$/)).toHaveLength(3)
  })
})
