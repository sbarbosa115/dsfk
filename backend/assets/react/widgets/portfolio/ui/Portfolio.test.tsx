import {screen} from '@testing-library/react';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {mockApi, renderWithProviders} from '@/shared/test/render';
import {Portfolio} from './Portfolio';

afterEach(() => vi.unstubAllGlobals());

describe('Portfolio', () => {
  it('shows each project’s budget, spending, progress and health, linking to its dashboard', async () => {
    mockApi({
      'GET /api/dashboard': [
        {
          id: 9,
          name: 'Torre Norte',
          status: 'ACTIVE',
          currency: 'COP',
          budgetApproved: true,
          budget: '4437506.25',
          deposited: '1100000.00',
          spent: '718753.12',
          progress: 1296,
          plannedProgress: 1500,
          executed: 1620,
          cpi: 0.95,
          spi: 0.86,
          alerts: 2,
        },
      ],
    });
    renderWithProviders(<Portfolio />);

    const card = (await screen.findByText('Torre Norte')).closest('a')!;
    expect(card).toHaveAttribute('href', '/projects/9?tab=dashboard');
    expect(card).toHaveTextContent('0,95 · Atención');
    expect(card).toHaveTextContent('0,86 · Crítico');
    expect(card).toHaveTextContent('2 alertas');
    expect(card).toHaveTextContent('Según el plan debería ir en 15%');
  });

  it('says when a project has nothing to measure yet, instead of two "Sin datos"', async () => {
    mockApi({
      'GET /api/dashboard': [
        {
          id: 3,
          name: 'Bodega Sur',
          status: 'DRAFT',
          currency: 'COP',
          budgetApproved: false,
          budget: '0.00',
          deposited: '0.00',
          spent: '0.00',
          progress: 0,
          plannedProgress: 0,
          executed: 0,
          cpi: null,
          spi: null,
          alerts: 0,
        },
      ],
    });
    renderWithProviders(<Portfolio />);

    const card = (await screen.findByText('Bodega Sur')).closest('a')!;
    expect(card).toHaveTextContent('Los indicadores aparecen');
    expect(card).not.toHaveTextContent('Sin datos');
  });
});
