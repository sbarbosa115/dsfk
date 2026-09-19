import { CssBaseline, ThemeProvider, Typography } from '@mui/material'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { lazy } from 'react'
import { createBrowserRouter, Navigate, RouterProvider } from 'react-router'
import { ApiError } from './api/client'
import { AuthProvider, useAuth } from './auth/AuthContext'
import { RequireAdmin, RequireAuth } from './auth/guards'
import i18n from './i18n'
import { AppLayout } from './layout/AppLayout'
import { LoginPage } from './pages/LoginPage'
import { theme } from './theme'

// Pages are loaded on demand to keep the initial bundle small.
const ProjectsPage = lazy(() => import('./pages/ProjectsPage').then((m) => ({ default: m.ProjectsPage })))
const ProjectDetailPage = lazy(() => import('./pages/ProjectDetailPage').then((m) => ({ default: m.ProjectDetailPage })))
const DashboardPage = lazy(() => import('./pages/DashboardPage').then((m) => ({ default: m.DashboardPage })))
const AuditPage = lazy(() => import('./pages/AuditPage').then((m) => ({ default: m.AuditPage })))
const UsersPage = lazy(() => import('./pages/UsersPage').then((m) => ({ default: m.UsersPage })))
const SettingsPage = lazy(() => import('./pages/SettingsPage').then((m) => ({ default: m.SettingsPage })))

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Client errors (401/403/404/422) will not succeed on retry.
      retry: (count, error) => !(error instanceof ApiError && error.status < 500) && count < 2,
      refetchOnWindowFocus: false,
    },
  },
})

/** Admins and PMs land on the dashboard; Team Leads on their projects. */
function HomeRedirect() {
  const { user } = useAuth()
  const manages = user?.admin || user?.memberships.some((m) => m.role === 'PROJECT_MANAGER')

  return <Navigate to={manages ? '/dashboard' : '/projects'} replace />
}

const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    element: <RequireAuth />,
    children: [
      {
        element: <AppLayout />,
        children: [
          { index: true, element: <HomeRedirect /> },
          { path: 'dashboard', element: <DashboardPage /> },
          { path: 'projects', element: <ProjectsPage /> },
          { path: 'projects/:id', element: <ProjectDetailPage /> },
          {
            element: <RequireAdmin />,
            children: [
              { path: 'users', element: <UsersPage /> },
              { path: 'settings', element: <SettingsPage /> },
              { path: 'audit', element: <AuditPage /> },
            ],
          },
          { path: '*', element: <Typography>{i18n.t('common.notFound')}</Typography> },
        ],
      },
    ],
  },
])

export default function App() {
  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <RouterProvider router={router} />
        </AuthProvider>
      </QueryClientProvider>
    </ThemeProvider>
  )
}
