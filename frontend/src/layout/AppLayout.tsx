import DashboardIcon from '@mui/icons-material/DashboardOutlined'
import FolderIcon from '@mui/icons-material/FolderOutlined'
import HistoryIcon from '@mui/icons-material/HistoryOutlined'
import KeyIcon from '@mui/icons-material/KeyOutlined'
import LogoutIcon from '@mui/icons-material/Logout'
import MenuIcon from '@mui/icons-material/Menu'
import PeopleIcon from '@mui/icons-material/PeopleOutlined'
import SettingsIcon from '@mui/icons-material/SettingsOutlined'
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
} from '@mui/material'
import { Suspense, useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { NavLink, Outlet, useNavigate } from 'react-router'
import { useAuth } from '../auth/AuthContext'
import { ChangePasswordDialog } from './ChangePasswordDialog'

const DRAWER_WIDTH = 240

export function AppLayout() {
  const { t } = useTranslation()
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const theme = useTheme()
  const desktop = useMediaQuery(theme.breakpoints.up('md'))
  const [open, setOpen] = useState(false)
  const [changingPassword, setChangingPassword] = useState(false)

  const manages = !!user?.admin || !!user?.memberships.some((m) => m.role === 'PROJECT_MANAGER')
  const items: { to: string; label: string; icon: ReactNode; admin?: boolean; hidden?: boolean }[] = [
    { to: '/dashboard', label: t('nav.dashboard'), icon: <DashboardIcon />, hidden: !manages },
    { to: '/projects', label: t('nav.projects'), icon: <FolderIcon /> },
    { to: '/users', label: t('nav.users'), icon: <PeopleIcon />, admin: true },
    { to: '/settings', label: t('nav.settings'), icon: <SettingsIcon />, admin: true },
    { to: '/audit', label: t('nav.audit'), icon: <HistoryIcon />, admin: true },
  ]

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
  }

  const drawer = (
    <>
      <Toolbar />
      <List>
        {items
          .filter((item) => !item.hidden && (!item.admin || user?.admin))
          .map((item) => (
            <ListItemButton key={item.to} component={NavLink} to={item.to} onClick={() => setOpen(false)}>
              <ListItemIcon>{item.icon}</ListItemIcon>
              <ListItemText primary={item.label} />
            </ListItemButton>
          ))}
      </List>
    </>
  )

  return (
    <Box sx={{ display: 'flex', minHeight: '100vh' }}>
      <AppBar position="fixed" elevation={0} sx={{ zIndex: (th) => th.zIndex.drawer + 1 }}>
        <Toolbar>
          {!desktop && (
            <IconButton color="inherit" edge="start" onClick={() => setOpen(true)} aria-label="menu" sx={{ mr: 1 }}>
              <MenuIcon />
            </IconButton>
          )}
          <Typography variant="h6" sx={{ flexGrow: 1 }} noWrap>
            {t('app.title')}
          </Typography>
          <Box sx={{ textAlign: 'right', mr: 1, display: { xs: 'none', sm: 'block' } }}>
            <Typography variant="body2">{user?.fullName}</Typography>
            <Typography variant="caption" sx={{ opacity: 0.8 }}>
              {user?.admin ? t('roles.ADMIN') : user?.email}
            </Typography>
          </Box>
          <IconButton color="inherit" onClick={() => setChangingPassword(true)} title={t('account.changePassword')} aria-label={t('account.changePassword')}>
            <KeyIcon />
          </IconButton>
          <IconButton color="inherit" onClick={handleLogout} title={t('nav.logout')} aria-label={t('nav.logout')}>
            <LogoutIcon />
          </IconButton>
        </Toolbar>
      </AppBar>

      <Drawer
        variant={desktop ? 'permanent' : 'temporary'}
        open={desktop || open}
        onClose={() => setOpen(false)}
        sx={{
          width: DRAWER_WIDTH,
          flexShrink: 0,
          '& .MuiDrawer-paper': { width: DRAWER_WIDTH, boxSizing: 'border-box' },
          '& a.active': { bgcolor: 'action.selected' },
        }}
      >
        {drawer}
      </Drawer>

      <Box component="main" sx={{ flexGrow: 1, p: { xs: 2, md: 3 }, minWidth: 0 }}>
        <Toolbar />
        {changingPassword && <ChangePasswordDialog onClose={() => setChangingPassword(false)} />}
        <Suspense fallback={<Typography color="text.secondary">{t('common.loading')}</Typography>}>
          <Outlet />
        </Suspense>
      </Box>
    </Box>
  )
}
