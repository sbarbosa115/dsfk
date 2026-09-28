import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {afterEach, describe, expect, it, vi} from 'vitest';
import type {Project} from '@/entities/project';
import {
  json,
  mockApi,
  renderWithProviders,
  sentBody,
} from '@/shared/test/render';
import {MembersPanel} from './MembersPanel';

const laura = {id: 2, email: 'pm@x.co', fullName: 'Laura Gómez'};
const carlos = {id: 3, email: 'lider@x.co', fullName: 'Carlos Pérez'};
const project: Project = {
  id: 9,
  name: 'Torre',
  description: null,
  currency: 'COP',
  status: 'DRAFT',
  plannedStart: null,
  plannedEnd: null,
  createdAt: '',
  myRole: 'ADMIN',
  members: [{id: 50, role: 'PROJECT_MANAGER', user: laura}],
};
const users = {
  items: [
    {
      ...laura,
      admin: false,
      superAdmin: false,
      active: true,
      createdAt: '',
      memberships: [],
    },
    {
      ...carlos,
      admin: false,
      superAdmin: false,
      active: true,
      createdAt: '',
      memberships: [],
    },
  ],
  total: 2,
  page: 1,
  perPage: 200,
};

afterEach(() => vi.unstubAllGlobals());

describe('MembersPanel', () => {
  it('adds someone not yet in the project, with a role', async () => {
    const fetchMock = mockApi({
      'GET /api/users': users,
      'POST /api/projects/9/members': {
        ...project,
        members: [
          ...project.members,
          {id: 51, role: 'TEAM_LEAD', user: carlos},
        ],
      },
    });
    renderWithProviders(<MembersPanel project={project} canManage />);

    const person = screen.getByLabelText('Persona');
    await screen.findByRole('option', {name: 'Carlos Pérez (lider@x.co)'});
    expect(
      within(person).queryByRole('option', {name: /Laura/}),
    ).not.toBeInTheDocument();
    await userEvent.selectOptions(person, '3');
    await userEvent.click(screen.getByRole('button', {name: 'Agregar'}));

    expect(
      await screen.findByText(
        'Carlos Pérez ahora es Líder de equipo del proyecto.',
      ),
    ).toBeInTheDocument();
    expect(sentBody(fetchMock, 'POST', '/api/projects/9/members')).toEqual({
      userId: 3,
      role: 'TEAM_LEAD',
    });
  });

  it('explains why a second Project Manager is refused', async () => {
    mockApi({
      'GET /api/users': users,
      'POST /api/projects/9/members': json(
        {error: 'project_manager_exists'},
        409,
      ),
    });
    renderWithProviders(<MembersPanel project={project} canManage />);

    await screen.findByRole('option', {name: 'Carlos Pérez (lider@x.co)'});
    await userEvent.selectOptions(screen.getByLabelText('Persona'), '3');
    await userEvent.selectOptions(
      screen.getByLabelText('Rol'),
      'PROJECT_MANAGER',
    );
    await userEvent.click(screen.getByRole('button', {name: 'Agregar'}));

    expect(
      await screen.findByText(
        'El proyecto ya tiene un gerente. Cámbiale el rol primero.',
      ),
    ).toBeInTheDocument();
  });

  it('asks before removing someone', async () => {
    const fetchMock = mockApi({
      'GET /api/users': users,
      'DELETE /api/projects/9/members/50': new Response(null, {status: 204}),
    });
    renderWithProviders(<MembersPanel project={project} canManage />);

    await userEvent.click(screen.getByRole('button', {name: 'Quitar'}));
    expect(
      screen.getByText(/Laura Gómez dejará de ver este proyecto/),
    ).toBeInTheDocument();
    await userEvent.click(
      within(screen.getByRole('dialog')).getByRole('button', {name: 'Quitar'}),
    );

    expect(
      await screen.findByText('Laura Gómez ya no está en el proyecto.'),
    ).toBeInTheDocument();
    expect(fetchMock).toHaveBeenCalledWith(
      '/api/projects/9/members/50',
      expect.objectContaining({method: 'DELETE'}),
    );
  });

  it('shows the team read-only to members', () => {
    mockApi({});
    renderWithProviders(
      <MembersPanel
        project={{...project, myRole: 'TEAM_LEAD'}}
        canManage={false}
      />,
    );

    expect(screen.getByText('Laura Gómez')).toBeInTheDocument();
    expect(
      screen.queryByRole('button', {name: 'Quitar'}),
    ).not.toBeInTheDocument();
    expect(screen.queryByLabelText('Persona')).not.toBeInTheDocument();
  });
});
