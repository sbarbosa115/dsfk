import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Navigate} from 'react-router';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {SessionProvider} from '@/entities/session';
import {mockApi, renderWithProviders} from '@/shared/test/render';
import {HelpPage} from './HelpPage';
import {HelpTopicPage} from './HelpTopicPage';

const person = (overrides: object) => ({
  id: 3,
  email: 'lider@x.co',
  fullName: 'Carlos',
  admin: false,
  superAdmin: false,
  memberships: [{projectId: 1, projectName: 'Torre', role: 'TEAM_LEAD'}],
  impersonator: null,
  canImpersonate: false,
  ...overrides,
});

afterEach(() => vi.unstubAllGlobals());

describe('HelpPage', () => {
  it('lists a Team Lead’s guides only, and finds one by a word without accents', async () => {
    mockApi({'GET /api/me': person({})});
    renderWithProviders(<HelpPage />, {wrapper: SessionProvider});

    expect(
      await screen.findByRole('link', {name: /Registrar un gasto/}),
    ).toHaveAttribute('href', '/help/registrar-gasto');
    expect(
      screen.queryByRole('link', {name: /Configurar los valores generales/}),
    ).not.toBeInTheDocument();
    expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();

    // The search box waits for a pause in the typing.
    await userEvent.type(screen.getByRole('searchbox'), 'rechazo');
    await vi.waitFor(() =>
      expect(
        screen.queryByRole('link', {name: /Entrar a la aplicación/}),
      ).not.toBeInTheDocument(),
    );
    expect(
      screen.getByRole('link', {name: /Seguir tus gastos/}),
    ).toBeInTheDocument();
  });

  it('lets an Admin open the other roles’ guides too', async () => {
    mockApi({'GET /api/me': person({admin: true, memberships: []})});
    renderWithProviders(<HelpPage />, {wrapper: SessionProvider});

    expect(
      await screen.findByRole('link', {
        name: /Configurar los valores generales/,
      }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole('link', {name: /Seguir tus gastos/}),
    ).not.toBeInTheDocument();
    await userEvent.click(
      screen.getByRole('checkbox', {name: /Ver todas las guías/}),
    );
    expect(
      screen.getByRole('link', {name: /Seguir tus gastos/}),
    ).toBeInTheDocument();
  });
});

describe('HelpTopicPage', () => {
  it('shows a guide’s numbered steps, notes and related guides', async () => {
    renderWithProviders(<Navigate to="/help/reembolsos" />, {
      routes: [{path: '/help/:id', element: <HelpTopicPage />}],
    });

    expect(
      await screen.findByRole('heading', {
        name: 'Reembolsar a los líderes de equipo',
      }),
    ).toBeInTheDocument();
    expect(screen.getAllByRole('listitem').length).toBeGreaterThan(2);
    expect(
      screen.getByRole('link', {name: 'Manejar la caja menor'}),
    ).toHaveAttribute('href', '/help/caja-menor');
  });
});
