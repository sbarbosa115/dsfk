import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { AuthProvider } from '../auth/AuthContext'
import { LoginPage } from './LoginPage'

function json(status: number, body: unknown) {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function renderLogin() {
  const router = createMemoryRouter(
    [
      { path: '/login', element: <LoginPage /> },
      { path: '/', element: <p>Inicio</p> },
    ],
    { initialEntries: ['/login'] },
  )
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  render(
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </QueryClientProvider>,
  )
}

afterEach(() => vi.unstubAllGlobals())

describe('LoginPage', () => {
  it('shows a Spanish error for invalid credentials', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async (url: string) => (url === '/api/me' ? json(401, { error: 'Authentication required.' }) : json(401, { error: 'invalid_credentials' }))),
    )
    renderLogin()

    await userEvent.type(await screen.findByLabelText(/correo/i), 'pm@example.com')
    await userEvent.type(screen.getByLabelText(/contraseña/i), 'wrong')
    await userEvent.click(screen.getByRole('button', { name: 'Ingresar' }))

    expect(await screen.findByText('Correo o contraseña incorrectos.')).toBeInTheDocument()
  })

  it('redirects after a successful login', async () => {
    const user = { id: 1, email: 'pm@example.com', fullName: 'PM', admin: false, memberships: [] }
    vi.stubGlobal(
      'fetch',
      vi.fn(async (url: string) => (url === '/api/me' ? json(401, {}) : json(200, user))),
    )
    renderLogin()

    await userEvent.type(await screen.findByLabelText(/correo/i), 'pm@example.com')
    await userEvent.type(screen.getByLabelText(/contraseña/i), 'secret-password')
    await userEvent.click(screen.getByRole('button', { name: 'Ingresar' }))

    expect(await screen.findByText('Inicio')).toBeInTheDocument()
  })
})
