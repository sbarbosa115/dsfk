import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { CurrentUser, Expense, ExpenseList } from '../../api/types'
import { ExpensesTab } from './ExpensesTab'

const noPermissions = { edit: false, attach: false, approve: false, reject: false, void: false, reimburse: false }

function expense(overrides: Partial<Expense>): Expense {
  return {
    id: 1,
    date: '2026-09-18',
    amount: '120000.00',
    description: 'Almuerzos cuadrilla',
    supplier: null,
    invoiceNumber: null,
    stage: { id: 5, name: 'Cimentación' },
    category: { id: 10, name: 'Materiales' },
    paidFrom: 'OUT_OF_POCKET',
    paidBy: { id: 3, fullName: 'Carlos' },
    status: 'SUBMITTED',
    rejectionReason: null,
    reimbursement: null,
    createdAt: '2026-09-18T10:00:00-05:00',
    attachments: [],
    permissions: noPermissions,
    ...overrides,
  }
}

const plan = {
  project: { id: 1, name: 'Torre', currency: 'COP', status: 'ACTIVE' },
  budgetStatus: 'APPROVED',
  categories: [{ id: 10, name: 'Materiales' }],
  stages: [{ id: 5, name: 'Cimentación', status: 'IN_PROGRESS' }],
  permissions: {},
}

let currentUser: CurrentUser
vi.mock('../../auth/AuthContext', () => ({ useAuth: () => ({ user: currentUser }) }))

function json(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function renderTab(list: ExpenseList) {
  const fetchMock = vi.fn(async (url: string, init?: RequestInit) => {
    if (init?.method === 'POST') return json(list.items[0], 201)
    if (url.includes('/expenses')) return json(list)

    return json(plan)
  })
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <ExpensesTab projectId="1" />
    </QueryClientProvider>,
  )

  return fetchMock
}

const summary = { pendingCount: 1, pendingTotal: '120000.00', toReimburseCount: 2, toReimburseTotal: '80000.00', teamLeadLimit: '500000.00' }

afterEach(() => vi.unstubAllGlobals())

describe('ExpensesTab', () => {
  it('shows a team lead what they are owed and records out-of-pocket expenses', async () => {
    currentUser = { id: 3, email: 'lead@x.co', fullName: 'Carlos', admin: false, memberships: [{ projectId: 1, projectName: 'Torre', role: 'TEAM_LEAD' }], impersonator: null, canImpersonate: false }
    const fetchMock = renderTab({ items: [expense({ status: 'REJECTED', rejectionReason: 'Falta la factura', permissions: { ...noPermissions, edit: true, attach: true } })], summary })

    expect(await screen.findByText('Te deben')).toBeInTheDocument()
    expect(screen.getByText('Rechazado: Falta la factura')).toBeInTheDocument()
    expect(screen.getByText('Sin soporte')).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: 'Registrar gasto' }))
    const dialog = screen.getByRole('dialog')
    expect(within(dialog).queryByLabelText('Pagado con')).not.toBeInTheDocument()
    await userEvent.type(within(dialog).getByLabelText(/Descripción/), 'Taxi')
    await userEvent.type(within(dialog).getByLabelText(/Monto/), '25.000')
    await userEvent.click(within(dialog).getByRole('button', { name: 'Guardar' }))

    const call = fetchMock.mock.calls.find(([url, init]) => url === '/api/projects/1/expenses' && init?.method === 'POST')!
    expect(JSON.parse(call[1]!.body as string)).toMatchObject({ paidFrom: 'OUT_OF_POCKET', amount: '25000', stageId: 5, categoryId: 10, description: 'Taxi' })
  })

  it('lets the PM approve and reimburse selected expenses', async () => {
    currentUser = { id: 2, email: 'pm@x.co', fullName: 'PM', admin: false, memberships: [{ projectId: 1, projectName: 'Torre', role: 'PROJECT_MANAGER' }], impersonator: null, canImpersonate: false }
    const fetchMock = renderTab({
      items: [
        expense({ id: 1, permissions: { ...noPermissions, approve: true, reject: true } }),
        expense({ id: 2, description: 'Taxi', amount: '30000.00', status: 'APPROVED', permissions: { ...noPermissions, reimburse: true } }),
        expense({ id: 3, description: 'Guantes', amount: '50000.00', status: 'APPROVED', permissions: { ...noPermissions, reimburse: true } }),
      ],
      summary,
    })

    await userEvent.click(await screen.findByRole('button', { name: 'Aprobar' }))
    expect(fetchMock).toHaveBeenCalledWith('/api/expenses/1/approve', expect.objectContaining({ method: 'POST' }))

    for (const box of screen.getAllByRole('checkbox', { name: 'Seleccionar para reembolso' })) {
      await userEvent.click(box)
    }
    await userEvent.click(screen.getByRole('button', { name: 'Reembolsar seleccionados (2)' }))
    const dialog = screen.getByRole('dialog')
    expect(within(dialog).getByText(/Total a reembolsar: \$\s80\.000/)).toBeInTheDocument()
    await userEvent.click(within(dialog).getByRole('button', { name: 'Guardar' }))

    const call = fetchMock.mock.calls.find(([url]) => url === '/api/projects/1/reimbursements')!
    expect(JSON.parse(call[1]!.body as string)).toMatchObject({ expenseIds: [2, 3], method: 'CASH' })
  })
})
