import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Finance, Movement } from '../../api/types'
import { FinanceTab } from './FinanceTab'

const finance: Finance = {
  currency: 'COP',
  budgetApproved: true,
  permissions: { deposit: true, void: true, drawContingency: true, completeStages: true },
  totals: { budget: '4937506.25', deposited: '2000000.00', spent: '450000.00', stagesAvailable: '1500000.00', pettyCash: '300000.00', contingency: '200000.00' },
  stages: [
    {
      id: 5,
      name: 'Cimentación',
      status: 'IN_PROGRESS',
      budget: '1437506.25',
      deposited: '1500000.00',
      contingencyDraws: '0.00',
      carriedIn: '0.00',
      carriedOut: '0.00',
      received: '1500000.00',
      available: '1500000.00',
      beyondBudget: '62493.75',
      spent: '1500000.00',
      remainingBudget: '-62493.75',
      executed: 10434,
      funded: 10434,
      nextStage: 'Estructura',
    },
  ],
  categories: [{ id: 10, name: 'Materiales', budget: '1437506.25', spent: '450000.00', executed: 3130 }],
  contingency: { budgeted: '500000.00', deposited: '200000.00', carriedIn: '0.00', drawn: '0.00', balance: '200000.00' },
  pettyCash: { deposited: '300000.00', balance: '300000.00' },
}

const movements: Movement[] = [
  {
    id: 1,
    type: 'DEPOSIT',
    date: '2026-09-18',
    amount: '500000.00',
    method: 'CASH',
    reference: null,
    note: null,
    createdBy: { id: 1, fullName: 'Admin' },
    createdAt: '2026-09-18T10:00:00-05:00',
    voided: { at: '2026-09-18T11:00:00-05:00', by: 'Admin', reason: 'Duplicado' },
    entries: [{ account: 'STAGE', stageId: 5, stageName: 'Cimentación', categoryId: null, categoryName: null, amount: '500000.00' }],
    attachments: [],
  },
]

const plan = { project: { id: 1 }, categories: [{ id: 10, name: 'Materiales' }], stages: [], permissions: {} }

function json(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function renderTab() {
  const fetchMock = vi.fn(async (url: string, init?: RequestInit) => {
    if (init?.method === 'POST') return json({ ...movements[0], id: 2, voided: null }, 201)
    if (url.endsWith('/finance')) return json(finance)
    if (url.endsWith('/movements')) return json(movements)

    return json(plan)
  })
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <FinanceTab projectId="1" />
    </QueryClientProvider>,
  )

  return fetchMock
}

const plain = (el: HTMLElement) => el.textContent!.replace(/\s/g, ' ')

afterEach(() => vi.unstubAllGlobals())

describe('FinanceTab', () => {
  it('shows balances, stage funding and voided movements', async () => {
    renderTab()

    expect(await screen.findByText('Total depositado')).toBeInTheDocument()
    const row = screen.getByText('Cimentación', { selector: 'p' }).closest('tr')!
    expect(plain(row)).toContain('$ 62.494')
    expect(plain(row)).toMatch(/104,3\s?%/)
    expect(await screen.findByText(/Anulado por Admin: “Duplicado”/)).toBeInTheDocument()
    expect(plain(screen.getByText('Materiales').parentElement!)).toMatch(/\$ 450\.000 \/ \$ 1\.437\.506 · 31,3\s?%/)
    expect(within(row).getByRole('button', { name: 'Finalizar etapa' })).toBeInTheDocument()
  })

  it('sends a split deposit with amounts in API notation', async () => {
    const fetchMock = renderTab()

    await userEvent.click(await screen.findByRole('button', { name: 'Registrar depósito' }))
    const dialog = screen.getByRole('dialog')
    await userEvent.type(within(dialog).getByLabelText(/Monto/), '1.500.000')
    await userEvent.click(within(dialog).getByRole('button', { name: 'Agregar destino' }))
    await userEvent.type(within(dialog).getAllByLabelText(/Monto/)[1], '250000,50')
    await userEvent.click(within(dialog).getByRole('button', { name: 'Guardar' }))

    const call = fetchMock.mock.calls.find(([url, init]) => url === '/api/projects/1/deposits' && init?.method === 'POST')!
    const body = JSON.parse(call[1]!.body as string)
    expect(body.method).toBe('TRANSFER')
    expect(body.allocations).toEqual([
      { destination: 'STAGE', stageId: 5, categoryId: null, amount: '1500000' },
      { destination: 'PETTY_CASH', stageId: null, categoryId: null, amount: '250000.50' },
    ])
  })
})
