import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {
  json,
  mockApi,
  renderWithProviders,
  sentBody,
} from '@/shared/test/render';
import {SettingsForm} from './SettingsForm';

const initial = {
  defaultCurrency: 'COP',
  teamLeadExpenseLimit: '500000',
  pettyCashLowBalancePercent: 20,
  budgetWarningPercents: [80, 100],
};

afterEach(() => vi.unstubAllGlobals());

describe('SettingsForm', () => {
  it('sends the limit as an API amount and shows what was stored', async () => {
    const fetchMock = mockApi({
      'PUT /api/settings': {
        ...initial,
        teamLeadExpenseLimit: '750000',
        budgetWarningPercents: [75, 100],
      },
    });
    renderWithProviders(<SettingsForm initial={initial} />);

    expect(screen.getByLabelText(/Límite por gasto/)).toHaveValue('500.000');
    const limit = screen.getByLabelText(/Límite por gasto/);
    await userEvent.clear(limit);
    await userEvent.type(limit, '750000');
    const thresholds = screen.getByLabelText(/^Alertas de presupuesto/);
    await userEvent.clear(thresholds);
    await userEvent.type(thresholds, '100, 75, 75');
    await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));

    expect(await screen.findByText('Cambios guardados.')).toBeInTheDocument();
    expect(sentBody(fetchMock, 'PUT', '/api/settings')).toMatchObject({
      teamLeadExpenseLimit: '750000',
      budgetWarningPercents: [100, 75, 75],
    });
    expect(thresholds).toHaveValue('75, 100');
  });

  it('marks the threshold field when one of its values is refused', async () => {
    mockApi({
      'PUT /api/settings': json(
        {
          error: 'validation_failed',
          violations: {
            'budgetWarningPercents[1]': [
              'Este valor debería estar entre 1 y 200.',
            ],
          },
        },
        422,
      ),
    });
    renderWithProviders(<SettingsForm initial={initial} />);

    await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));

    expect(
      await screen.findByText('Este valor debería estar entre 1 y 200.'),
    ).toBeInTheDocument();
  });
});
