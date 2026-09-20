import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createMemoryRouter, RouterProvider } from 'react-router'
import type { CurrentUser } from '../api/types'
import { AuthProvider } from '../auth/AuthContext'
import { ImpersonationBanner, ImpersonationMenu } from './ImpersonationMenu'

const admin: CurrentUser = { id: 1, email: 'admin@x.co', fullName: 'Administrador', admin: true, superAdmin: true, memberships: [], impersonator: null, canImpersonate: true }
const pm: CurrentUser = {
  id: 2, email: 'pm@x.co', fullName: 'Laura Gómez', admin: false, superAdmin: false,
  memberships: [{ projectId: 1, projectName: 'Torre', role: 'PROJECT_MANAGER' }],
  impersonator: { id: 1, fullName: 'Administrador' }, canImpersonate: true,
}
const users = [
  { ...admin, active: true, createdAt: '' },
  { id: 2, email: 'pm@x.co', fullName: 'Laura Gómez', admin: false, active: true, createdAt: '', memberships: pm.memberships },
  { id: 3, email: 'old@x.co', fullName: 'Inactivo', admin: false, active: false, createdAt: '', memberships: [] },
]

function json(body: unknown) {
  return new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } })
}

function renderWith(me: CurrentUser) {
  const fetchMock = vi.fn(async (url: string) => {
    if (url === '/api/me') return json(me)
    if (url === '/api/users') return json(users)
    if (url.startsWith('/api/impersonate?_switch_user=pm')) return json(pm)

    return json(admin)
  })
  vi.stubGlobal('fetch', fetchMock)
  const router = createMemoryRouter([{ path: '*', element: <><ImpersonationMenu /><ImpersonationBanner /></> }])
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </QueryClientProvider>,
  )

  return fetchMock
}

afterEach(() => vi.unstubAllGlobals())

describe('Ver como', () => {
  it('lists active non-admin users with their roles and switches with a POST', async () => {
    const fetchMock = renderWith(admin)

    await userEvent.click(await screen.findByRole('button', { name: 'Ver como otro usuario' }))
    expect(await screen.findByText('Gerente de proyecto · Torre')).toBeInTheDocument()
    expect(screen.queryByText('Inactivo')).not.toBeInTheDocument()

    await userEvent.click(screen.getByText('Laura Gómez'))

    expect(fetchMock).toHaveBeenCalledWith('/api/impersonate?_switch_user=pm%40x.co', expect.objectContaining({ method: 'POST' }))
    expect(await screen.findByText(/Estás viendo la aplicación como Laura Gómez/)).toBeInTheDocument()
  })

  it('shows the banner with the way back while impersonating', async () => {
    const fetchMock = renderWith(pm)

    await userEvent.click(await screen.findByRole('button', { name: 'Volver a Administrador' }))

    expect(fetchMock).toHaveBeenCalledWith('/api/impersonate?_switch_user=_exit', expect.objectContaining({ method: 'POST' }))
  })

  it('is hidden for users who cannot impersonate', async () => {
    renderWith({ ...pm, impersonator: null, canImpersonate: false })

    await screen.findByText((_, el) => el?.tagName === 'BODY')
    expect(screen.queryByRole('button', { name: 'Ver como otro usuario' })).not.toBeInTheDocument()
  })
})
