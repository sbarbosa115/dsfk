import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {SessionProvider} from '@/entities/session';
import {json, mockApi, renderWithProviders} from '@/shared/test/render';
import {LoginForm} from './LoginForm';

afterEach(() => vi.unstubAllGlobals());

function renderLogin() {
  renderWithProviders(<LoginForm />, {
    path: '/login',
    routes: [{path: '/', element: <p>Inicio</p>}],
    wrapper: SessionProvider,
  });
}

describe('LoginForm', () => {
  it('shows a Spanish message for wrong credentials', async () => {
    mockApi({
      'GET /api/me': json({error: 'authentication_required'}, 401),
      'POST /api/login': json({error: 'invalid_credentials'}, 401),
    });
    renderLogin();

    await userEvent.type(
      await screen.findByLabelText(/correo/i),
      'pm@example.com',
    );
    await userEvent.type(screen.getByLabelText(/contraseña/i), 'wrong');
    await userEvent.click(screen.getByRole('button', {name: 'Ingresar'}));

    expect(
      await screen.findByText('Correo o contraseña incorrectos.'),
    ).toBeInTheDocument();
  });

  it('keeps Ingresar disabled until both fields are filled', async () => {
    mockApi({'GET /api/me': json({}, 401)});
    renderLogin();

    const submit = await screen.findByRole('button', {name: 'Ingresar'});
    expect(submit).toBeDisabled();
    await userEvent.type(screen.getByLabelText(/correo/i), 'pm@example.com');
    expect(submit).toBeDisabled();
    await userEvent.type(screen.getByLabelText(/contraseña/i), 'x');
    expect(submit).toBeEnabled();
  });

  it('goes home after signing in', async () => {
    mockApi({
      'GET /api/me': json({}, 401),
      'POST /api/login': {
        id: 1,
        email: 'pm@example.com',
        fullName: 'PM',
        admin: false,
        superAdmin: false,
        memberships: [],
        impersonator: null,
        canImpersonate: false,
      },
    });
    renderLogin();

    await userEvent.type(
      await screen.findByLabelText(/correo/i),
      'pm@example.com',
    );
    await userEvent.type(
      screen.getByLabelText(/contraseña/i),
      'secret-password',
    );
    await userEvent.click(screen.getByRole('button', {name: 'Ingresar'}));

    expect(await screen.findByText('Inicio')).toBeInTheDocument();
  });
});
