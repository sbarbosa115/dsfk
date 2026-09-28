import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {expenseFixture, expensePageFixture} from '@/entities/expense';
import {planFixture} from '@/entities/plan';
import {mockApi, renderWithProviders, sentBody} from '@/shared/test/render';
import {ExpenseBoard} from './ExpenseBoard';

// Intl puts non-breaking spaces in amounts.
const text = (value: string) => value.replace(/\s/g, ' ');
const approvedPlan = () => planFixture({budgetStatus: 'APPROVED'});

afterEach(() => vi.unstubAllGlobals());

describe('ExpenseBoard', () => {
  it('lets the PM approve a Team Lead’s expense, warning when it goes on to an Admin', async () => {
    const big = expenseFixture({amount: '600000.00'});
    const fetchMock = mockApi({
      'GET /api/projects/9/plan': approvedPlan(),
      'GET /api/projects/9/expenses': expensePageFixture([big]),
      'POST /api/expenses/71/approve': {...big, status: 'PM_APPROVED'},
    });
    renderWithProviders(<ExpenseBoard projectId={9} manager />);

    const row = (await screen.findByText('Cemento')).closest('tr')!;
    expect(row).toHaveAttribute('title', 'Pendiente');
    expect(
      within(row).getByText('Pagado por Carlos Pérez'),
    ).toBeInTheDocument();
    await userEvent.click(within(row).getByRole('button', {name: 'Aprobar'}));
    const dialog = screen.getByRole('dialog');
    expect(text(dialog.textContent ?? '')).toContain(
      'Supera el límite de $ 500.000',
    );
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Aprobar'}),
    );

    await vi.waitFor(() =>
      expect(
        fetchMock.mock.calls.some(
          ([url, init]) =>
            url === '/api/expenses/71/approve' && init?.method === 'POST',
        ),
      ).toBe(true),
    );
  });

  it('records a Team Lead’s expense paid out of pocket, amounts as the API reads them', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/plan': approvedPlan(),
      'GET /api/projects/9/expenses': expensePageFixture([]),
      'POST /api/projects/9/expenses': expenseFixture(),
    });
    renderWithProviders(<ExpenseBoard projectId={9} manager={false} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Registrar gasto'}),
    );
    const dialog = screen.getByRole('dialog');
    expect(
      within(dialog).queryByLabelText('Se paga desde'),
    ).not.toBeInTheDocument();
    await userEvent.type(
      within(dialog).getByLabelText('Descripción'),
      'Cemento',
    );
    await userEvent.type(within(dialog).getByLabelText(/^Monto/), '120.000,5');
    await userEvent.type(
      within(dialog).getByLabelText(/^Proveedor/),
      'Ferretería',
    );
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Registrar'}),
    );

    await vi.waitFor(() =>
      expect(
        sentBody(fetchMock, 'POST', '/api/projects/9/expenses'),
      ).toMatchObject({
        stageId: 11,
        categoryId: 1,
        amount: '120000.5',
        description: 'Cemento',
        supplier: 'Ferretería',
        invoiceNumber: null,
        paidFrom: 'OUT_OF_POCKET',
      }),
    );
  });

  it('asks the PM where the money comes from', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/plan': approvedPlan(),
      'GET /api/projects/9/expenses': expensePageFixture([]),
      'POST /api/projects/9/expenses': expenseFixture(),
    });
    renderWithProviders(<ExpenseBoard projectId={9} manager />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Registrar gasto'}),
    );
    const dialog = screen.getByRole('dialog');
    await userEvent.type(
      within(dialog).getByLabelText('Descripción'),
      'Clavos',
    );
    await userEvent.type(within(dialog).getByLabelText(/^Monto/), '45000');
    await userEvent.selectOptions(
      within(dialog).getByLabelText('Se paga desde'),
      'Caja menor',
    );
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Registrar'}),
    );

    await vi.waitFor(() =>
      expect(
        sentBody(fetchMock, 'POST', '/api/projects/9/expenses'),
      ).toMatchObject({paidFrom: 'PETTY_CASH', amount: '45000'}),
    );
  });

  it('rejects with the reason the Team Lead will read', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/plan': approvedPlan(),
      'GET /api/projects/9/expenses': expensePageFixture([expenseFixture()]),
      'POST /api/expenses/71/reject': expenseFixture({status: 'REJECTED'}),
    });
    renderWithProviders(<ExpenseBoard projectId={9} manager />);

    const row = (await screen.findByText('Cemento')).closest('tr')!;
    await userEvent.click(within(row).getByRole('button', {name: 'Rechazar'}));
    const dialog = screen.getByRole('dialog');
    await userEvent.type(
      within(dialog).getByLabelText(/^Motivo del rechazo/),
      'Falta la factura',
    );
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Rechazar'}),
    );

    await vi.waitFor(() =>
      expect(sentBody(fetchMock, 'POST', '/api/expenses/71/reject')).toEqual({
        reason: 'Falta la factura',
      }),
    );
  });

  it('pays back the chosen approved expenses in one reimbursement', async () => {
    const approved = (id: number, amount: string) =>
      expenseFixture({
        id,
        amount,
        status: 'APPROVED',
        permissions: {
          ...expenseFixture().permissions,
          approve: false,
          reject: false,
          reimburse: true,
        },
      });
    const fetchMock = mockApi({
      'GET /api/projects/9/plan': approvedPlan(),
      'GET /api/projects/9/expenses': (_: unknown, url: string) =>
        url.includes('status=APPROVED')
          ? expensePageFixture([
              approved(71, '120000.00'),
              approved(72, '50000.00'),
            ])
          : expensePageFixture([approved(71, '120000.00')], {
              toReimburseCount: 2,
              toReimburseTotal: '170000.00',
            }),
      'POST /api/projects/9/reimbursements': [],
    });
    renderWithProviders(<ExpenseBoard projectId={9} manager />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Reembolsar'}),
    );
    const dialog = screen.getByRole('dialog');
    const boxes = await within(dialog).findAllByRole('checkbox');
    await userEvent.click(boxes[0]!);
    await userEvent.click(boxes[1]!);
    await userEvent.click(
      within(dialog).getByRole('button', {name: /Reembolsar \$\s170\.000/}),
    );

    await vi.waitFor(() =>
      expect(
        sentBody(fetchMock, 'POST', '/api/projects/9/reimbursements'),
      ).toMatchObject({
        expenseIds: [71, 72],
        method: 'TRANSFER',
        reference: null,
      }),
    );
  });

  it('says spending waits for the approved budget', async () => {
    mockApi({
      'GET /api/projects/9/plan': planFixture(),
      'GET /api/projects/9/expenses': expensePageFixture([]),
    });
    renderWithProviders(<ExpenseBoard projectId={9} manager />);

    expect(
      await screen.findByText(
        /Los gastos se registran cuando el administrador aprueba/,
      ),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole('button', {name: 'Registrar gasto'}),
    ).not.toBeInTheDocument();
  });
});
