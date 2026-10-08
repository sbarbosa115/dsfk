import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {SessionProvider} from '@/entities/session';
import {mockApi, renderWithProviders, sentBody} from '@/shared/test/render';
import {UserList} from './UserList';

const admin = {
  id: 1,
  email: 'admin@x.co',
  fullName: 'Sofía',
  admin: true,
  superAdmin: true,
  memberships: [],
  impersonator: null,
  canImpersonate: true,
};
const user = (overrides: object) => ({
  id: 5,
  email: 'carlos@x.co',
  fullName: 'Carlos Ruiz',
  admin: false,
  superAdmin: false,
  active: true,
  memberships: [],
  ...overrides,
});
const page = (items: object[]) => ({
  items,
  total: items.length,
  page: 1,
  perPage: 50,
});

afterEach(() => vi.unstubAllGlobals());

async function openMenu(name: string) {
  const row = (await screen.findByText(name)).closest('tr')!;
  await userEvent.click(
    within(row).getByRole('button', {name: `Más acciones: ${name}`}),
  );
  return {row, menu: screen.getByRole('menu', {name})};
}

describe('UserList', () => {
  it('edits from the row and asks before turning a user off, from the last item of its menu', async () => {
    const fetchMock = mockApi({
      'GET /api/me': admin,
      'GET /api/users': page([user({})]),
      'PATCH /api/users/5': user({active: false}),
      'GET /api/impersonate': [],
    });
    renderWithProviders(<UserList />, {wrapper: SessionProvider});

    const {row, menu} = await openMenu('Carlos Ruiz');
    expect(within(row).getByRole('button', {name: 'Editar'})).toHaveClass(
      'is-main',
      'btn-action-edit',
    );
    const items = within(menu).getAllByRole('menuitem');
    expect(items.at(-1)).toHaveTextContent('Desactivar');

    await userEvent.click(items.at(-1)!);
    const dialog = await screen.findByRole('dialog');
    expect(fetchMock).not.toHaveBeenCalledWith(
      '/api/users/5',
      expect.anything(),
    );
    await userEvent.click(
      within(dialog).getByRole('button', {name: 'Desactivar'}),
    );
    await vi.waitFor(() =>
      expect(sentBody(fetchMock, 'PATCH', '/api/users/5')).toEqual({
        active: false,
      }),
    );
  });

  it('turns an inactive user back on without asking', async () => {
    const fetchMock = mockApi({
      'GET /api/me': admin,
      'GET /api/users': page([user({active: false})]),
      'PATCH /api/users/5': user({active: true}),
      'GET /api/impersonate': [],
    });
    renderWithProviders(<UserList />, {wrapper: SessionProvider});

    const {menu} = await openMenu('Carlos Ruiz');
    await userEvent.click(
      within(menu).getByRole('menuitem', {name: /Activar/}),
    );
    await vi.waitFor(() =>
      expect(sentBody(fetchMock, 'PATCH', '/api/users/5')).toEqual({
        active: true,
      }),
    );
    expect(screen.queryByRole('dialog')).toBeNull();
  });

  it('never offers to turn yourself off', async () => {
    mockApi({
      'GET /api/me': admin,
      'GET /api/users': page([
        user({id: 1, fullName: 'Sofía', superAdmin: true}),
      ]),
    });
    renderWithProviders(<UserList />, {wrapper: SessionProvider});

    const row = (await screen.findAllByText('Sofía'))
      .map((el) => el.closest('tr'))
      .find(Boolean)!;
    expect(within(row).getByRole('button', {name: 'Editar'})).toBeEnabled();
    expect(
      within(row).queryByRole('button', {name: /Más acciones/}),
    ).toBeNull();
  });
});
