import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createContext, useContext, type ReactNode } from 'react'
import { api, ApiError } from '../api/client'
import type { CurrentUser } from '../api/types'

interface AuthValue {
  user: CurrentUser | null
  loading: boolean
  login: (email: string, password: string) => Promise<CurrentUser>
  logout: () => Promise<void>
  /** "Ver como": an email to view the app as that user, or '_exit' to return to the admin. */
  impersonate: (identifier: string) => Promise<CurrentUser>
}

const AuthContext = createContext<AuthValue | null>(null)

export const ME_KEY = ['me']
/** Kept across user switches so the admin can jump between users while impersonating. */
export const IMPERSONATION_USERS_KEY = ['impersonation-users']

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient()

  const me = useQuery({
    queryKey: ME_KEY,
    queryFn: async () => {
      try {
        return await api<CurrentUser>('/me')
      } catch (e) {
        if (e instanceof ApiError && e.status === 401) {
          return null
        }
        throw e
      }
    },
    staleTime: Infinity,
  })

  const loginMutation = useMutation({
    mutationFn: (credentials: { email: string; password: string }) =>
      api<CurrentUser>('/login', { method: 'POST', body: credentials }),
    onSuccess: (user) => queryClient.setQueryData(ME_KEY, user),
  })

  const logoutMutation = useMutation({
    mutationFn: () => api<void>('/logout', { method: 'POST' }),
    onSettled: () => {
      queryClient.clear()
      queryClient.setQueryData(ME_KEY, null)
    },
  })

  const impersonateMutation = useMutation({
    // The firewall switches the session and redirects to /api/impersonate, which returns the new user.
    mutationFn: (identifier: string) => api<CurrentUser>(`/impersonate?_switch_user=${encodeURIComponent(identifier)}`, { method: 'POST' }),
    onSuccess: (user) => {
      // Everything cached belongs to the previous identity.
      queryClient.removeQueries({ predicate: (q) => q.queryKey[0] !== IMPERSONATION_USERS_KEY[0] })
      queryClient.setQueryData(ME_KEY, user)
    },
  })

  const value: AuthValue = {
    user: me.data ?? null,
    loading: me.isPending,
    login: (email, password) => loginMutation.mutateAsync({ email, password }),
    logout: () => logoutMutation.mutateAsync(),
    impersonate: (identifier) => impersonateMutation.mutateAsync(identifier),
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthValue {
  const value = useContext(AuthContext)
  if (!value) {
    throw new Error('useAuth must be used inside AuthProvider')
  }

  return value
}
