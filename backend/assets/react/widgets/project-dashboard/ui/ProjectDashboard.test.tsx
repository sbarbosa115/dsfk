import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import type {ProjectDashboard as Dashboard} from '@/entities/dashboard';
import {mockApi, renderWithProviders} from '@/shared/test/render';
import {ProjectDashboard} from './ProjectDashboard';

const text = (value: string) => value.replace(/\s/g, ' ');

afterEach(() => vi.unstubAllGlobals());

function dashboard(overrides: Partial<Dashboard> = {}): Dashboard {
  return {
    currency: 'COP',
    budgetApproved: true,
    totals: {
      budget: '4437506.25',
      contingency: '500000.00',
      deposited: '1100000.00',
      spent: '718753.12',
      available: '381246.88',
      pettyCash: '100000.00',
    },
    progress: 1296,
    plannedProgress: 1296,
    executed: 1620,
    earnedValue: '575002.50',
    plannedValue: '575002.50',
    cpi: 0.8,
    spi: 1,
    forecastAtCompletion: '5546882.77',
    varianceAtCompletion: '-1109376.52',
    stages: [
      {
        id: 11,
        name: 'Cimentación',
        status: 'IN_PROGRESS',
        budget: '1437506.25',
        spent: '718753.12',
        executed: 5000,
        progress: 4000,
        plannedProgress: 4000,
        earnedValue: '575002.50',
        plannedValue: '575002.50',
        cpi: 0.8,
        spi: 1,
        plannedStart: '2026-10-01',
        plannedEnd: '2026-12-15',
        actualStart: '2026-10-01',
        actualEnd: null,
        delayed: false,
      },
    ],
    monthly: [{month: '2026-10', deposited: '1100000.00', spent: '718753.12'}],
    alerts: [
      {
        level: 'warning',
        code: 'milestones_overdue',
        stage: null,
        executed: null,
        plannedEnd: null,
        count: 2,
      },
      {
        level: 'error',
        code: 'stage_over_budget',
        stage: 'Estructura',
        executed: 10450,
        plannedEnd: null,
        count: null,
      },
    ],
    ...overrides,
  };
}

describe('ProjectDashboard', () => {
  it('reads the indices in words and lists what needs attention', async () => {
    mockApi({'GET /api/projects/9/dashboard': dashboard()});
    renderWithProviders(<ProjectDashboard projectId={9} />);

    expect(await screen.findAllByText('0,80 · Crítico')).toHaveLength(2);
    expect(screen.getByText('1,00 · Bien')).toBeInTheDocument();
    expect(
      text(
        screen.getByText('Costo final estimado').parentElement!.textContent ??
          '',
      ),
    ).toContain('$ 5.546.883');
    expect(
      screen.getByText('2 hitos pasaron su fecha planeada sin cumplirse.'),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'La etapa “Estructura” ya gastó el 104,5% de su presupuesto.',
      ),
    ).toBeInTheDocument();
  });

  it('shows each chart’s figures as a table on request', async () => {
    mockApi({'GET /api/projects/9/dashboard': dashboard()});
    renderWithProviders(<ProjectDashboard projectId={9} />);

    const card = (await screen.findByText('Dinero por mes')).closest(
      'section',
    )!;
    await userEvent.click(
      within(card).getByRole('button', {name: 'Ver como tabla'}),
    );
    expect(text(within(card).getByRole('table').textContent ?? '')).toContain(
      '$ 1.100.000',
    );
  });

  it('says when there is nothing to worry about', async () => {
    mockApi({
      'GET /api/projects/9/dashboard': dashboard({
        alerts: [],
        cpi: null,
        spi: null,
      }),
    });
    renderWithProviders(<ProjectDashboard projectId={9} />);

    expect(await screen.findByText('Nada por ahora.')).toBeInTheDocument();
    expect(screen.getAllByText('Sin datos').length).toBeGreaterThan(0);
  });

  it('says there is no money yet instead of drawing an empty axis', async () => {
    mockApi({
      'GET /api/projects/9/dashboard': dashboard({
        monthly: [
          {month: '2026-09', deposited: '0.00', spent: '0.00'},
          {month: '2026-10', deposited: '0.00', spent: '0.00'},
        ],
      }),
    });
    renderWithProviders(<ProjectDashboard projectId={9} />);

    expect(
      await screen.findByText(/Aún no hay depósitos ni gastos/),
    ).toBeInTheDocument();
  });
});
