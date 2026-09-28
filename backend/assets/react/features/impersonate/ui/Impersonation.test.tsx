import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {SessionProvider, type CurrentUser} from '@/entities/session';
import {mockApi, renderWithProviders} from '@/shared/test/render';
import {ImpersonationBanner} from './ImpersonationBanner';
import {ImpersonationPicker} from './ImpersonationPicker';

const admin: CurrentUser = {
  id: 1,
  email: 'admin@x.co',
  fullName: 'Administrador',
  admin: true,
  superAdmin: true,
  memberships: [],
  impersonator: null,
  canImpersonate: true,
};
const pm: CurrentUser = {
  id: 2,
  email: 'pm@x.co',
  fullName: 'Laura Gómez',
  admin: false,
  superAdmin: false,
  memberships: [{projectId: 1, projectName: 'Torre', role: 'PROJECT_MANAGER'}],
  impersonator: {id: 1, fullName: 'Administrador'},
  canImpersonate: true,
};
const page = {
  items: [
    {...admin, active: true, createdAt: ''},
    {...pm, active: true, createdAt: ''},
    {
      ...pm,
      id: 3,
      email: 'old@x.co',
      fullName: 'Inactivo',
      active: false,
      createdAt: '',
    },
  ],
  total: 3,
  page: 1,
  perPage: 200,
};

function renderAs(me: CurrentUser) {
  const fetchMock = mockApi({
    'GET /api/me': me,
    'GET /api/users': page,
    'POST /api/impersonate?_switch_user=pm%40x.co': pm,
    'POST /api/impersonate?_switch_user=_exit': admin,
  });
  renderWithProviders(
    <>
      <ImpersonationPicker />
      <ImpersonationBanner />
    </>,
    {wrapper: SessionProvider},
  );

  return fetchMock;
}

afterEach(() => vi.unstubAllGlobals());

describe('Ver como', () => {
  it('offers active non-admin users with their roles and switches with a POST', async () => {
    const fetchMock = renderAs(admin);

    const select = await screen.findByLabelText('Ver como');
    expect(
      await screen.findByRole('option', {
        name: 'Laura Gómez · Gerente de proyecto Torre',
      }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole('option', {name: /Inactivo/}),
    ).not.toBeInTheDocument();
    expect(
      screen.queryByRole('option', {name: /Administrador ·/}),
    ).not.toBeInTheDocument();

    await userEvent.selectOptions(select, 'pm@x.co');

    expect(fetchMock).toHaveBeenCalledWith(
      '/api/impersonate?_switch_user=pm%40x.co',
      expect.objectContaining({method: 'POST'}),
    );
    expect(
      await screen.findByText(/Estás viendo la aplicación como Laura Gómez/),
    ).toBeInTheDocument();
  });

  it('offers the way back on every page while viewing as someone', async () => {
    const fetchMock = renderAs(pm);

    await userEvent.click(
      await screen.findByRole('button', {name: 'Volver a Administrador'}),
    );

    expect(fetchMock).toHaveBeenCalledWith(
      '/api/impersonate?_switch_user=_exit',
      expect.objectContaining({method: 'POST'}),
    );
  });

  it('is not there for users who cannot use it', async () => {
    renderAs({...pm, impersonator: null, canImpersonate: false});

    await screen.findByText((_, el) => el?.tagName === 'BODY');
    expect(screen.queryByLabelText('Ver como')).not.toBeInTheDocument();
  });
});
