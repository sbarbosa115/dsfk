import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {json, mockApi, renderWithProviders} from '@/shared/test/render';
import {SettingsForm} from './SettingsForm';

const initial = {
  defaultCurrency: 'COP',
  teamLeadExpenseLimit: '500000',
  pettyCashLowBalancePercent: 20,
  budgetWarningPercents: [80, 100],
};

afterEach(() => vi.unstubAllGlobals());

describe('SettingsForm', () => {
  it('shows the stored values after saving (thresholds sorted, no repeats)', async () => {
    mockApi({
      'PUT /api/settings': {...initial, budgetWarningPercents: [75, 100]},
    });
    renderWithProviders(<SettingsForm initial={initial} />);

    const field = screen.getByLabelText('Alertas de presupuesto (%)');
    await userEvent.clear(field);
    await userEvent.type(field, '100, 75, 75');
    await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));

    expect(await screen.findByText('Cambios guardados.')).toBeInTheDocument();
    expect(field).toHaveValue('75, 100');
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
