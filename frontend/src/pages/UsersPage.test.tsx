import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { CurrentUser, User } from '../api/types'
import { AuthProvider } from '../auth/AuthContext'
import { UsersPage } from './UsersPage'

const root: CurrentUser = {
  id: 1, email: 'root@x.co', fullName: 'Administrador', admin: true, superAdmin: true,
  memberships: [], impersonator: null, canImpersonate: true,
}
const plainAdmin: CurrentUser = { ...root, id: 2, email: 'admin@x.co', superAdmin: false, canImpersonate: false }

const rows: User[] = [
  { id: 1, email: 'root@x.co', fullName: 'Administrador', admin: true, superAdmin: true, active: true, createdAt: '' },
  { id: 3, email: 'pm@x.co', fullName: 'Laura Gómez', admin: false, superAdmin: false, active: true, createdAt: '' },
]

function json(body: unknown) {
  return new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } })
}

function renderPage(me: CurrentUser) {
  const fetchMock = vi.fn(async (url: string, init?: RequestInit) => (url === '/api/users' && init?.method !== 'POST' ? json(rows) : json(me)))
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <AuthProvider>
        <UsersPage />
      </AuthProvider>
    </QueryClientProvider>,
  )

  return fetchMock
}

afterEach(() => vi.unstubAllGlobals())

describe('Usuarios', () => {
  it('marks who is a super admin in the list', async () => {
    renderPage(root)

    expect(await screen.findByText('Super administrador')).toBeInTheDocument()
    expect(screen.getByText('Laura Gómez')).toBeInTheDocument()
  })

  it('lets a super admin grant the level, sending it to the API', async () => {
    const user = userEvent.setup()
    const fetchMock = renderPage(root)

    // Second row: Laura Gómez, the user being promoted.
    await user.click((await screen.findAllByLabelText('Editar'))[1])
    const toggle = screen.getByRole('switch', { name: /Super administrador/ })
    expect(toggle).not.toBeChecked()

    await user.click(toggle)
    await user.click(screen.getByRole('button', { name: 'Guardar' }))

    const call = fetchMock.mock.calls.find(([url, init]) => url === '/api/users/3' && init?.method === 'PATCH')!
    expect(JSON.parse(call[1]!.body as string)).toMatchObject({ superAdmin: true, admin: true })
  })

  it('hides the super admin switch from an ordinary admin', async () => {
    const user = userEvent.setup()
    renderPage(plainAdmin)

    await user.click((await screen.findAllByLabelText('Editar'))[1])

    expect(screen.getByRole('switch', { name: 'Administrador' })).toBeInTheDocument()
    expect(screen.queryByRole('switch', { name: /Super administrador/ })).not.toBeInTheDocument()
  })
})
