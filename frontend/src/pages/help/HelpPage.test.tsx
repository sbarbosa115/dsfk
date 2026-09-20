import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { cleanup, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { describe, expect, it, vi } from 'vitest'
import type { CurrentUser } from '../../api/types'
import { AuthProvider } from '../../auth/AuthContext'
import { HelpPage } from './HelpPage'
import { HelpTopicPage } from './HelpTopicPage'

const teamLead: CurrentUser = {
  id: 3, email: 'lider@x.co', fullName: 'Carlos Ruiz', admin: false, superAdmin: false,
  memberships: [{ projectId: 1, projectName: 'Torre', role: 'TEAM_LEAD' }],
  impersonator: null, canImpersonate: false,
}
const admin: CurrentUser = { ...teamLead, id: 1, fullName: 'Administrador', admin: true, memberships: [] }
const superAdmin: CurrentUser = { ...admin, id: 0, superAdmin: true }

function renderHelp(me: CurrentUser, path = '/help') {
  vi.stubGlobal('fetch', vi.fn(async () => new Response(JSON.stringify(me), { status: 200, headers: { 'Content-Type': 'application/json' } })))
  const router = createMemoryRouter(
    [
      { path: '/help', element: <HelpPage /> },
      { path: '/help/:topicId', element: <HelpTopicPage /> },
    ],
    { initialEntries: [path] },
  )

  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </QueryClientProvider>,
  )
}

describe('Ayuda', () => {
  it('shows a team lead only the guides for their role', async () => {
    renderHelp(teamLead)

    expect(await screen.findByText('Registrar un gasto')).toBeInTheDocument()
    expect(screen.getByText('Qué puede hacer cada rol')).toBeInTheDocument()
    // Admin and manager guides stay out of their list.
    expect(screen.queryByText('Configurar los valores generales')).not.toBeInTheDocument()
    expect(screen.queryByText('Registrar un depósito y distribuirlo')).not.toBeInTheDocument()
    // Nothing to opt into, so no switch.
    expect(screen.queryByLabelText('Ver también las guías de otros roles')).not.toBeInTheDocument()
  })

  it('filters the list as the user types, ignoring accents', async () => {
    const user = userEvent.setup()
    renderHelp(admin)

    expect(await screen.findByText('Consultar la auditoría')).toBeInTheDocument()

    await user.type(screen.getByLabelText('Buscar en la ayuda'), 'auditoria')

    expect(screen.getByText('Consultar la auditoría')).toBeInTheDocument()
    expect(screen.queryByText('Crear y administrar usuarios')).not.toBeInTheDocument()
  })

  it('tells the user when nothing matches', async () => {
    const user = userEvent.setup()
    renderHelp(admin)

    await user.type(await screen.findByLabelText('Buscar en la ayuda'), 'tractor')

    expect(screen.getByText(/No encontramos temas/)).toBeInTheDocument()
  })

  it('lets an admin opt into the guides of the other roles', async () => {
    const user = userEvent.setup()
    renderHelp(admin)

    expect(await screen.findByText('Consultar la auditoría')).toBeInTheDocument()
    expect(screen.queryByText('Reembolsar a los líderes de equipo')).not.toBeInTheDocument()

    await user.click(screen.getByLabelText('Ver también las guías de otros roles'))

    expect(screen.getByText('Reembolsar a los líderes de equipo')).toBeInTheDocument()
  })

  it('opens a topic with its steps, screenshot and related links', async () => {
    renderHelp(teamLead, '/help/registrar-gasto')

    expect(await screen.findByRole('heading', { name: 'Registrar un gasto' })).toBeInTheDocument()
    expect(screen.getByText('Presiona Registrar gasto.')).toBeInTheDocument()
    expect(screen.getByAltText('Formulario Registrar gasto.')).toBeInTheDocument()
    // Related topics are filtered by role too: the manager-only approval guide is not offered.
    expect(screen.getByText('Seguir tus gastos y cobrar lo que te deben')).toBeInTheDocument()
    expect(screen.queryByText('Revisar, aprobar o rechazar gastos')).not.toBeInTheDocument()
  })

  it('offers the "Ver como" guide to a super admin but not to an ordinary admin', async () => {
    renderHelp(superAdmin)
    expect(await screen.findByText('Ver la aplicación como otro usuario')).toBeInTheDocument()

    cleanup()
    renderHelp(admin)
    expect(await screen.findByText('Consultar la auditoría')).toBeInTheDocument()
    expect(screen.queryByText('Ver la aplicación como otro usuario')).not.toBeInTheDocument()
  })

  it('refuses a topic that does not belong to the role', async () => {
    renderHelp(teamLead, '/help/configuracion')

    expect(await screen.findByText(/no corresponde a tu rol/)).toBeInTheDocument()
  })
})
