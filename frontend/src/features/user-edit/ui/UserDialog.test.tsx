import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {useState} from 'react';
import {
  SessionProvider,
  useSession,
  type CurrentUser,
} from '@/entities/session';
import type {User} from '@/entities/user';
import {mockApi, renderWithProviders, sentBody} from '@/shared/test/render';
import {UserDialog} from './UserDialog';

const root: CurrentUser = {
  id: 1,
  email: 'root@x.co',
  fullName: 'Administrador',
  admin: true,
  superAdmin: true,
  memberships: [],
  impersonator: null,
  canImpersonate: true,
};
const laura: User = {
  id: 3,
  email: 'pm@x.co',
  fullName: 'Laura Gómez',
  admin: false,
  superAdmin: false,
  active: true,
  createdAt: '',
  memberships: [],
};

/** Opens the dialog from a button once the session is loaded, as the users list does. */
function Host({user, onClose}: {user: User | null; onClose: () => void}) {
  const session = useSession();
  const [open, setOpen] = useState(false);
  if (!session.user) {
    return null;
  }

  return (
    <>
      <button onClick={() => setOpen(true)}>Abrir</button>
      {open && (
        <UserDialog
          user={user}
          onClose={() => {
            setOpen(false);
            onClose();
          }}
        />
      )}
    </>
  );
}

async function renderDialog(me: CurrentUser, user: User | null) {
  const fetchMock = mockApi({
    'GET /api/me': me,
    'PATCH /api/users/3': laura,
    'POST /api/users': laura,
  });
  const onClose = vi.fn();
  renderWithProviders(<Host user={user} onClose={onClose} />, {
    wrapper: SessionProvider,
  });
  await userEvent.click(await screen.findByRole('button', {name: 'Abrir'}));

  return {fetchMock, onClose};
}

afterEach(() => vi.unstubAllGlobals());

describe('UserDialog', () => {
  it('lets a super admin grant super admin, which also sends admin', async () => {
    const {fetchMock, onClose} = await renderDialog(root, laura);

    const toggle = await screen.findByRole('switch', {
      name: /Super administrador/,
    });
    expect(toggle).not.toBeChecked();
    await userEvent.click(toggle);
    await userEvent.click(screen.getByRole('button', {name: 'Guardar'}));

    await vi.waitFor(() => expect(onClose).toHaveBeenCalled());
    expect(sentBody(fetchMock, 'PATCH', '/api/users/3')).toMatchObject({
      superAdmin: true,
      admin: true,
    });
  });

  it('hides the super admin switch from an ordinary admin', async () => {
    await renderDialog(
      {...root, id: 2, superAdmin: false, canImpersonate: false},
      laura,
    );

    expect(
      await screen.findByRole('switch', {name: 'Administrador'}),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole('switch', {name: /Super administrador/}),
    ).not.toBeInTheDocument();
  });

  it('does not let admins lock themselves out', async () => {
    await renderDialog(root, {...laura, id: 1, admin: true, superAdmin: true});

    expect(
      await screen.findByRole('switch', {name: 'Administrador'}),
    ).toBeDisabled();
    expect(screen.getByRole('switch', {name: 'Activo'})).toBeDisabled();
  });

  it('creates a user with a required password and no password on edit unless typed', async () => {
    const {fetchMock} = await renderDialog(root, null);

    await userEvent.type(
      await screen.findByLabelText(/Nombre completo/),
      'Ana',
    );
    await userEvent.type(screen.getByLabelText(/Correo/), 'ana@x.co');
    await userEvent.type(screen.getByLabelText(/^Contraseña/), 'password-123');
    await userEvent.click(screen.getByRole('button', {name: 'Crear'}));

    await vi.waitFor(() =>
      expect(sentBody(fetchMock, 'POST', '/api/users')).toEqual({
        fullName: 'Ana',
        email: 'ana@x.co',
        password: 'password-123',
        admin: false,
        superAdmin: false,
      }),
    );
  });
});
