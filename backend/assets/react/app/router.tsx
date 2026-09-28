import {lazy} from 'react';
import {createBrowserRouter} from 'react-router';
import {LoginPage} from '@/pages/login';
import {AppShell} from '@/widgets/app-shell';
import {HomeRedirect, RequireAdmin, RequireSession} from './guards';

// Every page behind the login is loaded on demand, to keep the first load small.
const UsersPage = lazy(() =>
  import('@/pages/users').then((m) => ({default: m.UsersPage})),
);
const SettingsPage = lazy(() =>
  import('@/pages/settings').then((m) => ({default: m.SettingsPage})),
);
const ProjectsPage = lazy(() =>
  import('@/pages/projects').then((m) => ({default: m.ProjectsPage})),
);
const ProjectDetailPage = lazy(() =>
  import('@/pages/project-detail').then((m) => ({
    default: m.ProjectDetailPage,
  })),
);
const DashboardPage = lazy(() =>
  import('@/pages/dashboard').then((m) => ({default: m.DashboardPage})),
);
const AuditPage = lazy(() =>
  import('@/pages/audit').then((m) => ({default: m.AuditPage})),
);
const HelpPage = lazy(() =>
  import('@/pages/help').then((m) => ({default: m.HelpPage})),
);
const HelpTopicPage = lazy(() =>
  import('@/pages/help').then((m) => ({default: m.HelpTopicPage})),
);
const NotFoundPage = lazy(() =>
  import('@/pages/not-found').then((m) => ({default: m.NotFoundPage})),
);

export const router = createBrowserRouter([
  {path: '/login', element: <LoginPage />},
  {
    element: <RequireSession />,
    children: [
      {
        element: <AppShell />,
        children: [
          {index: true, element: <HomeRedirect />},
          {path: 'dashboard', element: <DashboardPage />},
          {path: 'projects', element: <ProjectsPage />},
          {path: 'projects/:id', element: <ProjectDetailPage />},
          {path: 'help', element: <HelpPage />},
          {path: 'help/:id', element: <HelpTopicPage />},
          {
            element: <RequireAdmin />,
            children: [
              {path: 'users', element: <UsersPage />},
              {path: 'settings', element: <SettingsPage />},
              {path: 'audit', element: <AuditPage />},
            ],
          },
          {path: '*', element: <NotFoundPage />},
        ],
      },
    ],
  },
]);
