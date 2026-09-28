import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {
  json,
  mockApi,
  renderWithProviders,
  sentBody,
} from '@/shared/test/render';
import {ChangePasswordButton} from './ChangePasswordButton';

afterEach(() => vi.unstubAllGlobals());

describe('Cambiar contraseña', () => {
  it('sends both passwords and confirms the change', async () => {
    const fetchMock = mockApi({
      'POST /api/me/password': new Response(null, {status: 204}),
    });
    renderWithProviders(<ChangePasswordButton />);

    await userEvent.click(
      screen.getByRole('button', {name: 'Cambiar contraseña'}),
    );
    await userEvent.type(
      screen.getByLabelText('Contraseña actual'),
      'old-password',
    );
    await userEvent.type(
      screen.getByLabelText(/Nueva contraseña/),
      'new-password-1',
    );
    await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));

    expect(
      await screen.findByText('Contraseña actualizada.'),
    ).toBeInTheDocument();
    expect(sentBody(fetchMock, 'POST', '/api/me/password')).toEqual({
      currentPassword: 'old-password',
      newPassword: 'new-password-1',
    });
  });

  it('shows the field error when the current password is wrong', async () => {
    mockApi({
      'POST /api/me/password': json(
        {
          error: 'validation_failed',
          violations: {
            currentPassword: ['La contraseña actual no es correcta.'],
          },
        },
        422,
      ),
    });
    renderWithProviders(<ChangePasswordButton />);

    await userEvent.click(
      screen.getByRole('button', {name: 'Cambiar contraseña'}),
    );
    await userEvent.type(screen.getByLabelText('Contraseña actual'), 'wrong');
    await userEvent.type(
      screen.getByLabelText(/Nueva contraseña/),
      'new-password-1',
    );
    await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));

    expect(
      await screen.findByText('La contraseña actual no es correcta.'),
    ).toBeInTheDocument();
  });
});
