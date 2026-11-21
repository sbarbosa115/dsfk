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
    expect(
      within(stage).getByRole('tab', {name: 'Partidas (1)'}),
      'a stage being written opens on its lines',
    ).toHaveAttribute('aria-selected', 'true');
    expect(within(stage).getByText('Concreto')).toBeInTheDocument();
    expect(within(stage).getByText('12,5 m³')).toBeInTheDocument();
    await userEvent.click(within(stage).getByRole('tab', {name: 'Hitos (2)'}));
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

  it('offers "Agregar etapa" once, at the end of the stage list\'s header, even with no stages', async () => {
    mockApi({'GET /api/projects/9/plan': planFixture({stages: []})});
    renderWithProviders(<PlanBoard projectId={9} />);

    expect(await screen.findByText(/Aún no hay etapas/)).toBeInTheDocument();
    expect(screen.getAllByRole('button', {name: 'Agregar etapa'})).toHaveLength(
      1,
    );
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
          addStage: false,
        },
        budget: null,
        issues: null,
        stages: plan.stages.map((s) => ({
          ...s,
          editable: false,
          deletable: false,
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

    await userEvent.click(await screen.findByRole('tab', {name: 'Hitos (2)'}));
    const row = screen.getByText('Excavación').closest('li')!;
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

  it('shows a stage added to the approved budget as an addition waiting for the Admin', async () => {
    const approved = planFixture();
    const original = approved.stages[0]!;
    const plan = planFixture({
      budgetStatus: 'APPROVED',
      permissions: {
        ...approved.permissions,
        edit: false,
        submit: true,
        track: true,
        addStage: true,
      },
      stages: [
        {...original, editable: false, deletable: false},
        {
          ...original,
          id: 12,
          name: 'Acabados exteriores',
          position: 1,
          weight: null,
          amendment: 1,
          approved: false,
          editable: true,
          milestones: [],
        },
      ],
      budget: {
        ...approved.budget!,
        approvedAt: '2026-10-01T10:00:00-05:00',
        amendments: [
          {
            number: 1,
            status: 'DRAFT',
            stages: ['Acabados exteriores'],
            total: '2000000.00',
            approvedAt: null,
          },
        ],
      },
    });
    mockApi({'GET /api/projects/9/plan': plan});
    renderWithProviders(<PlanBoard projectId={9} />);

    const added = await screen.findByRole('region', {
      name: 'Acabados exteriores',
    });
    expect(text(added.textContent ?? '')).toContain('Adición 1 · Borrador');
    expect(text(added.textContent ?? '')).toContain('Pendiente de aprobación');
    expect(
      within(added).queryByRole('button', {name: 'Iniciar etapa'}),
      'it starts once the Admin approves it',
    ).not.toBeInTheDocument();
    expect(
      within(added).getByRole('button', {name: 'Agregar partida'}),
    ).toBeInTheDocument();

    const first = screen.getByRole('region', {name: 'Cimentación'});
    expect(
      within(first).getByRole('button', {name: 'Iniciar etapa'}),
    ).toBeInTheDocument();
    expect(
      within(first).queryByRole('button', {name: 'Agregar partida'}),
      'what was approved stays locked',
    ).not.toBeInTheDocument();

    expect(
      screen.getByRole('button', {name: 'Enviar adición'}),
    ).toBeInTheDocument();
    const list = screen.getByRole('table', {name: 'Adiciones al presupuesto'});
    expect(text(list.textContent ?? '')).toContain('Acabados exteriores');
    expect(text(list.textContent ?? '')).toContain('$ 2.000.000');

    await userEvent.click(screen.getByRole('button', {name: 'Agregar etapa'}));
    expect(
      screen.getByRole('dialog'),
      'the form says what happens to a stage added now',
    ).toHaveTextContent('se agrega como adición');
  });

  it('offers to delete an approved stage only when the server says it can go', async () => {
    const base = planFixture();
    const original = base.stages[0]!;
    mockApi({
      'GET /api/projects/9/plan': planFixture({
        budgetStatus: 'APPROVED',
        permissions: {...base.permissions, edit: false, track: true},
        stages: [
          {...original, editable: false, deletable: true},
          {
            ...original,
            id: 12,
            name: 'Estructura',
            position: 1,
            status: 'IN_PROGRESS',
            editable: false,
            deletable: false,
          },
        ],
      }),
    });
    renderWithProviders(<PlanBoard projectId={9} />);

    const pending = await screen.findByRole('region', {name: 'Cimentación'});
    expect(
      within(pending).getByRole('button', {name: 'Eliminar etapa'}),
    ).toBeInTheDocument();
    const started = screen.getByRole('region', {name: 'Estructura'});
    expect(
      within(started).queryByRole('button', {name: 'Eliminar etapa'}),
    ).not.toBeInTheDocument();
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

    await userEvent.click(await screen.findByRole('tab', {name: 'Hitos (2)'}));
    await userEvent.click(screen.getByRole('button', {name: 'Agregar hito'}));
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

  describe('the stage list', () => {
    // 20 nov 2026: Simentación runs 26 oct – 31 dic, so 25 of its 66 days are gone (38 %).
    const running = () => {
      const base = planFixture();
      const original = base.stages[0]!;
      return planFixture({
        budgetStatus: 'APPROVED',
        permissions: {...base.permissions, edit: false, track: true},
        stages: [
          {
            ...original,
            name: 'Demolición',
            status: 'COMPLETED',
            progress: 10000,
            editable: false,
            deletable: false,
            milestones: [
              {
                ...original.milestones[0]!,
                id: 20,
                name: 'Demolición total',
                weight: 10000,
                completedAt: '2026-10-19',
                completedBy: {id: 5, fullName: 'Eduardo Ruiz'},
              },
            ],
          },
          {
            ...original,
            id: 12,
            name: 'Simentación',
            position: 1,
            status: 'IN_PROGRESS',
            plannedStart: '2026-10-26',
            plannedEnd: '2026-12-31',
            actualStart: '2026-10-26',
            editable: false,
            deletable: false,
            milestones: [
              {
                ...original.milestones[0]!,
                name: 'Replanteo',
                plannedDate: '2026-10-27',
                overdue: true,
              },
              {
                ...original.milestones[1]!,
                name: 'Parrilla de zapata',
                plannedDate: '2026-11-25',
              },
            ],
          },
        ],
      });
    };

    it('sums the stages up and opens only the running one, on its milestones', async () => {
      vi.useFakeTimers({
        now: new Date('2026-11-20T12:00:00'),
        toFake: ['Date'],
      });
      mockApi({'GET /api/projects/9/plan': running()});
      renderWithProviders(<PlanBoard projectId={9} />);

      const current = await screen.findByRole('region', {name: 'Simentación'});
      expect(text(screen.getByText(/^2 etapas/).textContent ?? '')).toBe(
        '2 etapas · 1 en curso · 1 finalizada · 1 hito atrasado',
      );

      const finished = screen.getByRole('region', {name: 'Demolición'});
      expect(
        within(finished).getByRole('button', {name: /Ver detalle/}),
      ).toHaveAttribute('aria-expanded', 'false');
      expect(
        within(finished).queryByText('Demolición total'),
        'a finished stage shows only its summary',
      ).not.toBeInTheDocument();

      expect(
        within(current).getByText(
          /^Va 0\s?% con el 37,9\s?% del tiempo transcurrido$/,
        ),
      ).toBeInTheDocument();
      expect(within(current).getByText('1 atrasado')).toBeInTheDocument();
      expect(
        within(current).getByRole('tab', {name: 'Hitos (2)'}),
      ).toHaveAttribute('aria-selected', 'true');
      expect(
        within(current).getByText(/Atrasado 24 días · era para el/),
      ).toBeInTheDocument();
      const next = within(current)
        .getByText('Parrilla de zapata')
        .closest('li')!;
      expect(within(next).getByText('Próximo')).toBeInTheDocument();
      expect(
        within(next).getByRole('button', {name: 'Marcar cumplido'}),
        'every milestone offers it, so it is outlined, not filled',
      ).not.toHaveClass('is-main');

      await userEvent.click(
        within(finished).getByRole('button', {name: /Ver detalle/}),
      );
      expect(
        within(finished).getByText(/Cumplido el .* · Eduardo Ruiz/),
      ).toBeInTheDocument();
      vi.useRealTimers();
    });

    it('filters the stages by status', async () => {
      mockApi({'GET /api/projects/9/plan': running()});
      renderWithProviders(<PlanBoard projectId={9} />);

      await screen.findByRole('region', {name: 'Simentación'});
      await userEvent.click(screen.getByRole('tab', {name: 'Finalizadas'}));
      expect(
        screen.getByRole('region', {name: 'Demolición'}),
      ).toBeInTheDocument();
      expect(
        screen.queryByRole('region', {name: 'Simentación'}),
      ).not.toBeInTheDocument();
      expect(
        text(
          screen.getByRole('region', {name: 'Demolición'}).textContent ?? '',
        ),
        'a stage keeps its number in the whole plan',
      ).toMatch(/^1/);

      await userEvent.click(screen.getByRole('tab', {name: 'Pendientes'}));
      expect(
        screen.getByText('Ninguna etapa en este estado.'),
      ).toBeInTheDocument();
    });

    it('lists the lines largest first, with their share of the stage', async () => {
      const base = planFixture();
      const stage = base.stages[0]!;
      mockApi({
        'GET /api/projects/9/plan': planFixture({
          stages: [
            {
              ...stage,
              budgetTotal: '1000000.00',
              lines: [
                {
                  ...stage.lines![0]!,
                  id: 31,
                  description: 'Lonas',
                  total: '250000.00',
                },
                {
                  ...stage.lines![0]!,
                  id: 32,
                  description: 'Nómina',
                  total: '750000.00',
                },
              ],
            },
          ],
        }),
      });
      renderWithProviders(<PlanBoard projectId={9} />);

      const region = await screen.findByRole('region', {name: 'Cimentación'});
      const rows = within(region).getAllByRole('row').slice(1);
      expect(
        rows.map((r) => within(r).getAllByRole('cell')[0]!.textContent),
      ).toEqual(['NóminaMateriales', 'LonasMateriales']);
      expect(
        within(rows[0]!).getByRole('progressbar', {name: /Nómina/}),
      ).toHaveAttribute('aria-valuenow', '75');
    });
  });
});
