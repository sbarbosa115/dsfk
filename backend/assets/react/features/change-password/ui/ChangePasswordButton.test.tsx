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

async function change(current: string, next: string) {
  await userEvent.click(
    screen.getByRole('button', {name: 'Cambiar contraseña'}),
  );
  await userEvent.type(screen.getByLabelText('Contraseña actual'), current);
  await userEvent.type(screen.getByLabelText(/Nueva contraseña/), next);
  await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));
}

describe('Cambiar contraseña', () => {
  it('sends both passwords and confirms the change', async () => {
    const fetchMock = mockApi({
      'POST /api/me/password': new Response(null, {status: 204}),
    });
    renderWithProviders(<ChangePasswordButton />);

    await change('old-password', 'new-password-1');

    expect(
      await screen.findByText('Contraseña actualizada.'),
    ).toBeInTheDocument();
    expect(sentBody(fetchMock, 'POST', '/api/me/password')).toEqual({
      currentPassword: 'old-password',
      newPassword: 'new-password-1',
    });
  });

  it('shows the message under the current password when it is wrong', async () => {
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

    await change('wrong', 'new-password-1');

    expect(
      await screen.findByText('La contraseña actual no es correcta.'),
    ).toBeInTheDocument();
    expect(screen.getByText('Revisa los campos marcados.')).toBeInTheDocument();
  });
});
