import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {SessionProvider} from '@/entities/session';
import {mockApi, renderWithProviders} from '@/shared/test/render';
import {ProjectList} from './ProjectList';

const project = {
  id: 9,
  name: 'Torre Norte',
  description: 'Doce pisos',
  currency: 'COP',
  status: 'ACTIVE',
  plannedStart: '2026-10-01',
  plannedEnd: '2027-06-30',
  createdAt: '',
  myRole: 'PROJECT_MANAGER',
  members: [],
};
const pm = {
  id: 2,
  email: 'pm@x.co',
  fullName: 'Laura',
  admin: false,
  superAdmin: false,
  memberships: [],
  impersonator: null,
  canImpersonate: false,
};

afterEach(() => vi.unstubAllGlobals());

describe('ProjectList', () => {
  it('shows each project with its dates, currency and the role of whoever looks, tinted by status', async () => {
    mockApi({
      'GET /api/me': pm,
      'GET /api/projects': {items: [project], total: 1, page: 1, perPage: 50},
    });
    renderWithProviders(<ProjectList />, {wrapper: SessionProvider});

    const name = await screen.findByText('Torre Norte');
    const row = name.closest('tr')!;
    expect(row).toHaveClass('row-tone-success');
    expect(row).toHaveAttribute('title', 'Activo');
    expect(row).toHaveTextContent('1 de oct de 2026 – 30 de jun de 2027');
    expect(row).toHaveTextContent('Gerente de proyecto');
    expect(
      screen.queryByRole('button', {name: 'Nuevo proyecto'}),
    ).not.toBeInTheDocument();
  });

  it('filters by status and offers a way back when nothing matches', async () => {
    const fetchMock = mockApi({
      'GET /api/me': {...pm, admin: true},
      'GET /api/projects': (_: unknown, url: string) =>
        url.includes('status=ARCHIVED')
          ? {items: [], total: 0, page: 1, perPage: 50}
          : {items: [project], total: 1, page: 1, perPage: 50},
    });
    renderWithProviders(<ProjectList />, {wrapper: SessionProvider});

    await screen.findByText('Torre Norte');
    await userEvent.selectOptions(screen.getByLabelText('Estado'), 'ARCHIVED');

    expect(
      await screen.findByText('Ningún proyecto coincide con la búsqueda.'),
    ).toBeInTheDocument();
    expect(fetchMock).toHaveBeenCalledWith(
      '/api/projects?status=ARCHIVED',
      expect.anything(),
    );
    await userEvent.click(screen.getByRole('button', {name: 'Ver todos'}));
    expect(await screen.findByText('Torre Norte')).toBeInTheDocument();
  });

  it('tells an admin with no projects to create the first one', async () => {
    mockApi({
      'GET /api/me': {...pm, admin: true},
      'GET /api/projects': {items: [], total: 0, page: 1, perPage: 50},
    });
    renderWithProviders(<ProjectList />, {wrapper: SessionProvider});

    expect(await screen.findByText(/Crea el primero/)).toBeInTheDocument();
    expect(
      screen.getAllByRole('button', {name: 'Nuevo proyecto'}),
    ).toHaveLength(2);
  });
});
