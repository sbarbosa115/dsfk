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
          {path: 'projects', element: <ProjectsPage />},
          {path: 'projects/:id', element: <ProjectDetailPage />},
          {
            element: <RequireAdmin />,
            children: [
              {path: 'users', element: <UsersPage />},
              {path: 'settings', element: <SettingsPage />},
            ],
          },
          {path: '*', element: <NotFoundPage />},
        ],
      },
    ],
  },
]);
