import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import type {Project} from '@/entities/project';
import {
  json,
  mockApi,
  renderWithProviders,
  sentBody,
} from '@/shared/test/render';
import {ProjectFormModal} from './ProjectFormModal';

const created: Project = {
  id: 9,
  name: 'Torre Norte',
  description: null,
  currency: 'COP',
  status: 'DRAFT',
  plannedStart: '2026-10-01',
  plannedEnd: null,
  createdAt: '',
  myRole: 'ADMIN',
  members: [],
};
const settings = {
  defaultCurrency: 'COP',
  teamLeadExpenseLimit: '500000',
  pettyCashLowBalancePercent: 20,
  budgetWarningPercents: [80, 100],
};

afterEach(() => vi.unstubAllGlobals());

describe('ProjectFormModal', () => {
  it('creates a project in the default currency, dates typed day first', async () => {
    const fetchMock = mockApi({
      'GET /api/settings': settings,
      'POST /api/projects': created,
    });
    const onSaved = vi.fn();
    renderWithProviders(
      <ProjectFormModal project={null} onClose={() => {}} onSaved={onSaved} />,
    );

    await userEvent.type(screen.getByLabelText('Nombre'), 'Torre Norte');
    expect(await screen.findByDisplayValue('COP')).toBeInTheDocument();
    await userEvent.type(
      screen.getByLabelText(/^Inicio planeado/),
      '1/10/2026',
    );
    await userEvent.click(screen.getByRole('button', {name: 'Crear'}));

    await vi.waitFor(() => expect(onSaved).toHaveBeenCalledWith(created));
    expect(sentBody(fetchMock, 'POST', '/api/projects')).toEqual({
      name: 'Torre Norte',
      description: '',
      status: 'DRAFT',
      plannedStart: '2026-10-01',
      plannedEnd: null,
      currency: 'COP',
    });
  });

  it('does not let the currency change once the project exists', async () => {
    mockApi({'GET /api/settings': settings});
    renderWithProviders(
      <ProjectFormModal project={created} onClose={() => {}} />,
    );

    expect(screen.getByLabelText(/^Moneda/)).toBeDisabled();
  });

  it('shows the API message under the end date', async () => {
    mockApi({
      'PATCH /api/projects/9': json(
        {
          error: 'validation_failed',
          violations: {
            plannedEnd: [
              'La fecha de fin no puede ser anterior a la de inicio.',
            ],
          },
        },
        422,
      ),
    });
    renderWithProviders(
      <ProjectFormModal project={created} onClose={() => {}} />,
    );

    await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));

    expect(
      await screen.findByText(
        'La fecha de fin no puede ser anterior a la de inicio.',
      ),
    ).toBeInTheDocument();
  });
});
