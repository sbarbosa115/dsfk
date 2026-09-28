import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {planFixture} from '@/entities/plan';
import {
  json,
  mockApi,
  renderWithProviders,
  sentBody,
} from '@/shared/test/render';
import {PlanBoard} from './PlanBoard';

// Intl puts non-breaking spaces in amounts.
const text = (value: string) => value.replace(/\s/g, ' ');

afterEach(() => vi.unstubAllGlobals());

describe('PlanBoard', () => {
  it('shows the budget figures, each stage with its lines and milestones', async () => {
    mockApi({'GET /api/projects/9/plan': planFixture()});
    renderWithProviders(<PlanBoard projectId={9} />);

    const stage = await screen.findByRole('region', {name: 'Cimentación'});
    expect(text(stage.textContent ?? '')).toContain('$ 1.437.506');
    expect(text(stage.textContent ?? '')).toContain('32,4% del presupuesto');
    expect(within(stage).getByText('Concreto')).toBeInTheDocument();
    expect(within(stage).getByText('12,5 m³')).toBeInTheDocument();
    expect(within(stage).getByText('Excavación')).toBeInTheDocument();
    expect(
      text(
        screen.getByText('Presupuesto total').parentElement!.textContent ?? '',
      ),
    ).toContain('$ 1.937.506');
    expect(
      screen.getByRole('button', {name: 'Enviar a aprobación'}),
    ).toBeEnabled();
  });

  it('lists what blocks submitting and keeps the button off', async () => {
    mockApi({
      'GET /api/projects/9/plan': planFixture({
        issues: [{code: 'milestone_weights', stageId: 11}],
        stages: planFixture().stages.map((s) => ({
          ...s,
          milestoneWeightTotal: 4000,
        })),
      }),
    });
    renderWithProviders(<PlanBoard projectId={9} />);

    expect(
      await screen.findByText(
        'Los hitos de la etapa “Cimentación” deben sumar 100 % (hoy suman 40%).',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {name: 'Enviar a aprobación'}),
    ).toBeDisabled();
  });

  it('shows a Team Lead the stages and milestones without money', async () => {
    const plan = planFixture();
    mockApi({
      'GET /api/projects/9/plan': {
        ...plan,
        permissions: {
          ...plan.permissions,
          viewFinancials: false,
          edit: false,
          manageCategories: false,
          submit: false,
        },
        budget: null,
        issues: null,
        stages: plan.stages.map((s) => ({
          ...s,
          budgetTotal: null,
          weight: null,
          lines: null,
        })),
      },
    });
    renderWithProviders(<PlanBoard projectId={9} />);

    expect(await screen.findByText('Excavación')).toBeInTheDocument();
    expect(screen.queryByText('Partidas')).not.toBeInTheDocument();
    expect(screen.queryByText(/\$/)).not.toBeInTheDocument();
    expect(
      screen.queryByRole('button', {name: 'Agregar etapa'}),
    ).not.toBeInTheDocument();
  });

  it('adds a line with the quantity and price the way the API reads them', async () => {
    const fetchMock = mockApi({
      'GET /api/projects/9/plan': planFixture(),
      'POST /api/stages/11/lines': planFixture(),
    });
    renderWithProviders(<PlanBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Agregar partida'}),
    );
    await userEvent.type(screen.getByLabelText('Descripción'), 'Acero');
    await userEvent.type(screen.getByLabelText(/^Unidad/), 'kg');
    await userEvent.type(screen.getByLabelText(/^Cantidad/), '1.200,5');
    await userEvent.type(screen.getByLabelText(/^Valor unitario/), '4500');
    expect(
      text(screen.getByText(/Total de la partida/).textContent ?? ''),
    ).toContain('$ 5.402.250');
    await userEvent.click(
      within(screen.getByRole('dialog')).getByRole('button', {
        name: 'Agregar partida',
      }),
    );

    await vi.waitFor(() =>
      expect(sentBody(fetchMock, 'POST', '/api/stages/11/lines')).toEqual({
        categoryId: 1,
        description: 'Acero',
        unit: 'kg',
        quantity: '1200.5',
        unitPrice: '4500',
      }),
    );
  });

  it('marks a milestone met once the budget is approved', async () => {
    const approved = planFixture({
      budgetStatus: 'APPROVED',
      permissions: {
        ...planFixture().permissions,
        edit: false,
        submit: false,
        track: true,
      },
    });
    const fetchMock = mockApi({
      'GET /api/projects/9/plan': approved,
      'POST /api/milestones/21/complete': approved,
    });
    renderWithProviders(<PlanBoard projectId={9} />);

    const row = (await screen.findByText('Excavación')).closest('tr')!;
    await userEvent.click(
      within(row).getByRole('button', {name: 'Marcar cumplido'}),
    );
    await userEvent.type(screen.getByLabelText(/^Notas/), 'Terminada');
    await userEvent.click(
      within(screen.getByRole('dialog')).getByRole('button', {
        name: 'Marcar cumplido',
      }),
    );

    await vi.waitFor(() =>
      expect(
        sentBody(fetchMock, 'POST', '/api/milestones/21/complete'),
      ).toMatchObject({notes: 'Terminada'}),
    );
  });

  it('shows the weight error under the field when a milestone would pass 100 %', async () => {
    mockApi({
      'GET /api/projects/9/plan': planFixture({
        stages: planFixture().stages.map((s) => ({
          ...s,
          milestoneWeightTotal: 6000,
        })),
      }),
      'POST /api/stages/11/milestones': json(
        {
          error: 'validation_failed',
          violations: {
            weight: [
              'La suma de los pesos de la etapa no puede superar el 100 %.',
            ],
          },
        },
        422,
      ),
    });
    renderWithProviders(<PlanBoard projectId={9} />);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Agregar hito'}),
    );
    expect(screen.getByLabelText(/^Peso/)).toHaveValue('40');
    await userEvent.type(screen.getByLabelText('Nombre del hito'), 'Curado');
    await userEvent.clear(screen.getByLabelText(/^Peso/));
    await userEvent.type(screen.getByLabelText(/^Peso/), '40,01');
    await userEvent.click(
      within(screen.getByRole('dialog')).getByRole('button', {
        name: 'Agregar hito',
      }),
    );

    expect(
      await screen.findByText(
        'La suma de los pesos de la etapa no puede superar el 100 %.',
      ),
    ).toBeInTheDocument();
  });
});
