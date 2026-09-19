import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Plan } from '../../api/types'
import { PlanTab } from './PlanTab'

const plain = (value: string) => value.replace(/\s/g, ' ')

function basePlan(overrides: Partial<Plan> = {}): Plan {
  return {
    project: { id: 1, name: 'Torre', currency: 'COP', status: 'DRAFT' },
    permissions: { viewFinancials: true, edit: true, manageCategories: true, submit: true, review: false, track: false, reopenMilestones: false },
    budgetStatus: 'DRAFT',
    progress: 0,
    categories: [{ id: 10, name: 'Materiales' }],
    stages: [
      {
        id: 5,
        name: 'Cimentación',
        position: 0,
        status: 'PENDING',
        plannedStart: '2026-10-01',
        plannedEnd: '2026-12-15',
        actualStart: null,
        actualEnd: null,
        progress: 0,
        milestoneWeightTotal: 4000,
        milestones: [{ id: 7, name: 'Excavación', weight: 4000, plannedDate: null, completedAt: null, completedBy: null, completionNotes: null, overdue: false }],
        budgetTotal: '437506.25',
        weight: 10000,
        lines: [{ id: 3, categoryId: 10, description: 'Concreto', unit: 'm³', quantity: '12.5', unitPrice: '35000.50', total: '437506.25' }],
      },
    ],
    budget: { contingency: '500000.00', stagesTotal: '437506.25', total: '937506.25', approvedAt: null, byCategory: [{ categoryId: 10, total: '437506.25' }], events: [] },
    issues: [{ code: 'milestone_weights', stageId: 5 }],
    ...overrides,
  }
}

function renderPlan(plan: Plan) {
  const fetchMock = vi.fn(async () => new Response(JSON.stringify(plan), { status: 200, headers: { 'Content-Type': 'application/json' } }))
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <PlanTab projectId="1" />
    </QueryClientProvider>,
  )

  return fetchMock
}

afterEach(() => vi.unstubAllGlobals())

describe('PlanTab', () => {
  it('shows totals and explains why the budget cannot be submitted yet', async () => {
    renderPlan(basePlan())

    expect(await screen.findByText('1. Cimentación')).toBeInTheDocument()
    expect(plain(screen.getByText(/937\.506/).textContent!)).toBe('$ 937.506')
    expect(screen.getByText(/Los hitos de la etapa “Cimentación” deben sumar 100% \(actualmente 40\s?%\)/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Enviar a aprobación' })).toBeDisabled()
    expect(screen.getByText('Concreto')).toBeInTheDocument()
  })

  it('hides money and editing for team leads', async () => {
    const plan = basePlan({
      permissions: { viewFinancials: false, edit: false, manageCategories: false, submit: false, review: false, track: false, reopenMilestones: false },
      budget: undefined,
      issues: undefined,
    })
    const stage = { ...plan.stages[0] }
    delete stage.budgetTotal
    delete stage.lines
    delete stage.weight
    renderPlan({ ...plan, stages: [stage] })

    await userEvent.click(await screen.findByText('1. Cimentación'))

    expect(screen.getByText('Excavación')).toBeInTheDocument()
    expect(screen.queryByText(/\$/)).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Agregar etapa' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Enviar a aprobación' })).not.toBeInTheDocument()
  })

  it('lets the admin approve a submitted budget', async () => {
    const plan = basePlan({
      budgetStatus: 'SUBMITTED',
      permissions: { viewFinancials: true, edit: false, manageCategories: true, submit: false, review: true, track: false, reopenMilestones: false },
      issues: [],
    })
    const fetchMock = renderPlan(plan)

    await userEvent.click(await screen.findByRole('button', { name: 'Aprobar presupuesto' }))
    const dialog = screen.getByRole('dialog')
    await userEvent.click(within(dialog).getByRole('button', { name: 'Aprobar presupuesto' }))

    expect(fetchMock).toHaveBeenCalledWith('/api/projects/1/budget/approve', expect.objectContaining({ method: 'POST' }))
  })
})
