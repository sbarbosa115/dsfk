import {Box, CircularProgress} from '@mui/material';
import {Navigate, Outlet, useLocation} from 'react-router';
import {isManager, useSession} from '@/entities/session';

export function RequireSession() {
  const {user, loading} = useSession();
  const location = useLocation();

  if (loading) {
    return (
      <Box sx={{display: 'grid', placeItems: 'center', minHeight: '100vh'}}>
        <CircularProgress />
      </Box>
    );
  }
  if (!user) {
    return <Navigate to="/login" replace state={{from: location.pathname}} />;
  }

  return <Outlet />;
}

export function RequireAdmin() {
  const {user} = useSession();

  return user?.admin ? <Outlet /> : <Navigate to="/" replace />;
}

/** Admins and PMs land on the dashboard; Team Leads on their projects. */
export function HomeRedirect() {
  const {user} = useSession();

  return <Navigate to={isManager(user) ? '/dashboard' : '/projects'} replace />;
}
