import DashboardIcon from '@mui/icons-material/DashboardOutlined';
import FolderIcon from '@mui/icons-material/FolderOutlined';
import HelpIcon from '@mui/icons-material/HelpOutlineOutlined';
import HistoryIcon from '@mui/icons-material/HistoryOutlined';
import LogoutIcon from '@mui/icons-material/Logout';
import MenuIcon from '@mui/icons-material/Menu';
import PeopleIcon from '@mui/icons-material/PeopleOutlined';
import SettingsIcon from '@mui/icons-material/SettingsOutlined';
import {
  AppBar,
  Box,
  Drawer,
  IconButton,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Toolbar,
  Typography,
  useMediaQuery,
  useTheme,
} from '@mui/material';
import {Suspense, useState, type ReactNode} from 'react';
import {useTranslation} from 'react-i18next';
import {NavLink, Outlet, useNavigate} from 'react-router';
import {isManager, useSession} from '@/entities/session';
import {ChangePasswordButton} from '@/features/change-password';
import {ImpersonationBanner, ImpersonationMenu} from '@/features/impersonate';

const DRAWER_WIDTH = 240;

interface NavItem {
  to: string;
  label: string;
  icon: ReactNode;
  visible: boolean;
}

/** The signed-in layout: top bar (who, "Ver como", password, sign out), side navigation and the page. */
export function AppShell() {
  const {t} = useTranslation();
  const {user, logout} = useSession();
  const navigate = useNavigate();
  const theme = useTheme();
  const desktop = useMediaQuery(theme.breakpoints.up('md'));
  const [open, setOpen] = useState(false);
  const admin = !!user?.admin;

  const items: NavItem[] = [
    {
      to: '/dashboard',
      label: t('nav.dashboard'),
      icon: <DashboardIcon />,
      visible: isManager(user),
    },
    {
      to: '/projects',
      label: t('nav.projects'),
      icon: <FolderIcon />,
      visible: true,
    },
    {to: '/users', label: t('nav.users'), icon: <PeopleIcon />, visible: admin},
    {
      to: '/settings',
      label: t('nav.settings'),
      icon: <SettingsIcon />,
      visible: admin,
    },
    {
      to: '/audit',
      label: t('nav.audit'),
      icon: <HistoryIcon />,
      visible: admin,
    },
    {to: '/help', label: t('nav.help'), icon: <HelpIcon />, visible: true},
  ];

  const handleLogout = async () => {
    await logout();
    navigate('/login', {replace: true});
  };

  return (
    <Box sx={{display: 'flex', minHeight: '100vh'}}>
      <AppBar
        position="fixed"
        elevation={0}
        sx={{zIndex: (th) => th.zIndex.drawer + 1}}
      >
        <Toolbar>
          {!desktop && (
            <IconButton
              color="inherit"
              edge="start"
              onClick={() => setOpen(true)}
              aria-label={t('nav.menu')}
              sx={{mr: 1}}
            >
              <MenuIcon />
            </IconButton>
          )}
          <Typography variant="h6" sx={{flexGrow: 1}} noWrap>
            {t('app.name')}
          </Typography>
          <Box
            sx={{textAlign: 'right', mr: 1, display: {xs: 'none', sm: 'block'}}}
          >
            <Typography variant="body2">{user?.fullName}</Typography>
            <Typography variant="caption" sx={{opacity: 0.8}}>
              {user?.superAdmin
                ? t('roles.SUPER_ADMIN')
                : user?.admin
                  ? t('roles.ADMIN')
                  : user?.email}
            </Typography>
          </Box>
          <ImpersonationMenu />
          <ChangePasswordButton />
          <IconButton
            color="inherit"
            onClick={() => void handleLogout()}
            title={t('nav.logout')}
            aria-label={t('nav.logout')}
          >
            <LogoutIcon />
          </IconButton>
        </Toolbar>
      </AppBar>

      <Drawer
        variant={desktop ? 'permanent' : 'temporary'}
        open={desktop || open}
        onClose={() => setOpen(false)}
        sx={{
          'width': DRAWER_WIDTH,
          'flexShrink': 0,
          '& .MuiDrawer-paper': {width: DRAWER_WIDTH, boxSizing: 'border-box'},
          '& a.active': {bgcolor: 'action.selected'},
        }}
      >
        <Toolbar />
        <List component="nav" aria-label={t('nav.main')}>
          {items
            .filter((item) => item.visible)
            .map((item) => (
              <ListItemButton
                key={item.to}
                component={NavLink}
                to={item.to}
                onClick={() => setOpen(false)}
              >
                <ListItemIcon>{item.icon}</ListItemIcon>
                <ListItemText primary={item.label} />
              </ListItemButton>
            ))}
        </List>
      </Drawer>

      <Box component="main" sx={{flexGrow: 1, p: {xs: 2, md: 3}, minWidth: 0}}>
        <Toolbar />
        <ImpersonationBanner />
        <Suspense
          fallback={
            <Typography color="text.secondary">
              {t('common.loading')}
            </Typography>
          }
        >
          <Outlet />
        </Suspense>
      </Box>
    </Box>
  );
}
