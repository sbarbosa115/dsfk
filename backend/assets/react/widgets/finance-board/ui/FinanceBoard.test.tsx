import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {depositFixture, financeFixture} from '@/entities/finance';
import {
  json,
  mockApi,
  renderWithProviders,
  sentBody,
} from '@/shared/test/render';
import {FinanceBoard} from './FinanceBoard';

// Intl puts non-breaking spaces in amounts.
const text = (value: string) => value.replace(/\s/g, ' ');

const page = (items: unknown[]) => ({
  items,
  total: items.length,
  page: 1,
  perPage: 50,
});

afterEach(() => vi.unstubAllGlobals());

describe('FinanceBoard', () => {
  it('shows where the money is: totals, each stage against its budget, and the movements', async () => {
    mockApi({
      'GET /api/projects/9/finance': financeFixture(),
      'GET /api/projects/9/movements': page([
        depositFixture(),
        depositFixture({
          id: 52,
          reference: 'DUP-1',
          voided: {
            at: '2026-10-15T11:00:00-05:00',
            by: 'Ana Admin',
            reason: 'Duplicado',
          },
        }),
      ]),
    });
    renderWithProviders(<FinanceBoard projectId={9} />);

    const deposited = await screen.findByText('Depositado');
    expect(text(deposited.parentElement!.textContent ?? '')).toContain(
      '$ 2.000.000',
    );
    const foundation = screen.getByText('Cimentación').closest('tr')!;
    expect(text(foundation.textContent ?? '')).toContain(
      '$ 62.493,75 por encima del presupuesto',
    );
    expect(
      within(foundation).getByRole('button', {name: 'Finalizar etapa'}),
    ).toBeInTheDocument();
    const structure = screen.getByText('Estructura').closest('tr')!;
    expect(
      within(structure).queryByRole('button', {name: 'Finalizar etapa'}),
    ).not.toBeInTheDocument();

    const voided = (await screen.findByText(/Anulado por Ana Admin/)).closest(
      'tr',
    )!;
    expect(voided).toHaveClass('row-inactive');
    expect(
      within(voided).queryByRole('button', {name: 'Anular'}),
    ).not.toBeInTheDocument();
    const live = screen.getByText('Transferencia · TRX-001').closest('tr')!;
    expect(
      text(within(live).getByText(/Cimentación · Materiales/).textContent!),
    ).toBe('Cimentación · Materiales: $ 1.500.000');
    expect(
      within(live).getByRole('button', {name: 'Adjuntar'}),
      'a deposit without its proof asks for it first',
    ).toHaveClass('is-main');
    await userEvent.click(
      within(live).getByRole('button', {
        name: 'Más acciones: Depósito · 15 de oct de 2026',
      }),
    );
    expect(
      screen.getAllByRole('menuitem').at(-1),
      'voiding is worded, and last',
    ).toHaveTextContent('Anular');
  });

  it('splits a deposit among a stage and the caja menor, sending amounts as the API reads them', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/finance': financeFixture(),
      'GET /api/projects/9/movements': page([]),
      'POST /api/projects/9/deposits': depositFixture(),
    });
    renderWithProviders(<FinanceBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Registrar depósito'}),
    );
    const dialog = screen.getByRole('dialog');
    await userEvent.type(within(dialog).getByLabelText(/^Referencia/), 'TRX-9');
    await userEvent.selectOptions(
      within(dialog).getByLabelText(/^Categoría 1/),
      'Materiales',
    );
    await userEvent.type(within(dialog).getByLabelText('Monto 1'), '1500000,5');
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Agregar destino'}),
    );
    await userEvent.selectOptions(
      within(dialog).getByLabelText('Destino 2'),
      'Caja menor',
    );
    await userEvent.type(within(dialog).getByLabelText('Monto 2'), '300000');
    expect(
      text(within(dialog).getByText(/Total del depósito/).textContent ?? ''),
    ).toContain('$ 1.800.000,5');
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Registrar'}),
    );

    await vi.waitFor(() =>
      expect(
        sentBody(fetchMock, 'POST', '/api/projects/9/deposits'),
      ).toMatchObject({
        method: 'TRANSFER',
        reference: 'TRX-9',
        note: null,
        allocations: [
          {
            destination: 'STAGE',
            stageId: 11,
            categoryId: 3,
            amount: '1500000.5',
          },
          {
            destination: 'PETTY_CASH',
            stageId: null,
            categoryId: null,
            amount: '300000',
          },
        ],
      }),
    );
    await vi.waitFor(() =>
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument(),
    );
  });

  it('shows a refused allocation under its own row', async () => {
    mockApi({
      'GET /api/projects/9/finance': financeFixture(),
      'GET /api/projects/9/movements': page([]),
      'POST /api/projects/9/deposits': json(
        {
          error: 'validation_failed',
          violations: {
            'allocations[1].stageId': ['La etapa ya está finalizada.'],
          },
        },
        422,
      ),
    });
    renderWithProviders(<FinanceBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Registrar depósito'}),
    );
    const dialog = screen.getByRole('dialog');
    await userEvent.type(within(dialog).getByLabelText('Monto 1'), '10');
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Agregar destino'}),
    );
    await userEvent.type(within(dialog).getByLabelText('Monto 2'), '10');
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Registrar'}),
    );

    const error = await within(dialog).findByText(
      'La etapa ya está finalizada.',
    );
    expect(error.closest('label')).toHaveTextContent('Destino 2');

    // Fixing the row clears the old message.
    await userEvent.type(within(dialog).getByLabelText('Monto 2'), '0');
    expect(
      within(dialog).queryByText('La etapa ya está finalizada.'),
    ).not.toBeInTheDocument();
  });

  it('voids a movement with the reason', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/finance': financeFixture(),
      'GET /api/projects/9/movements': page([depositFixture()]),
      'POST /api/movements/51/void': depositFixture(),
    });
    renderWithProviders(<FinanceBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {
        name: 'Más acciones: Depósito · 15 de oct de 2026',
      }),
    );
    await userEvent.click(screen.getByRole('menuitem', {name: /Anular/}));
    const dialog = await screen.findByRole('dialog');
    expect(text(dialog.textContent ?? '')).toContain(
      'Depósito del 15 de oct de 2026 por $ 2.000.000 (Cimentación · Materiales, Caja menor, Contingencia)',
    );
    await userEvent.type(
      within(dialog).getByLabelText('Motivo de la anulación'),
      'Duplicado',
    );
    await userEvent.click(within(dialog).getByRole('button', {name: 'Anular'}));

    await vi.waitFor(() =>
      expect(sentBody(fetchMock, 'POST', '/api/movements/51/void')).toEqual({
        reason: 'Duplicado',
      }),
    );
  });

  it('says where a completed stage’s money goes before completing it', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/finance': financeFixture(),
      'GET /api/projects/9/movements': page([]),
      'POST /api/stages/11/complete': financeFixture(),
    });
    renderWithProviders(<FinanceBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Finalizar etapa'}),
    );
    const dialog = screen.getByRole('dialog');
    expect(text(dialog.textContent ?? '')).toContain(
      'Su saldo disponible ($ 1.500.000) pasará a la etapa “Estructura”.',
    );
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Finalizar etapa'}),
    );

    await vi.waitFor(() =>
      expect(
        sentBody(fetchMock, 'POST', '/api/stages/11/complete'),
      ).toHaveProperty('actualEnd'),
    );
  });

  it('gives the Project Manager the figures without the Admin’s actions', async () => {
    mockApi({
      'GET /api/projects/9/finance': financeFixture({
        permissions: {
          deposit: false,
          void: false,
          drawContingency: false,
          completeStages: false,
        },
      }),
      'GET /api/projects/9/movements': page([
        depositFixture({
          attachments: [
            {id: 7, name: 'slip.pdf', mimeType: 'application/pdf', size: 10},
          ],
        }),
      ]),
    });
    renderWithProviders(<FinanceBoard projectId={9} />);

    const proof = await screen.findByRole('link', {name: 'Comprobante'});
    expect(proof).toHaveAttribute('href', '/api/attachments/7');
    expect(
      screen.queryByRole('button', {name: 'Registrar depósito'}),
    ).not.toBeInTheDocument();
    expect(
      screen.queryByRole('button', {name: /Más acciones/}),
      'nothing to void or add for the PM, so no menu either',
    ).not.toBeInTheDocument();
    expect(
      screen.queryByRole('button', {name: 'Finalizar etapa'}),
    ).not.toBeInTheDocument();
  });
});
