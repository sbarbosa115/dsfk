import SwitchIcon from '@mui/icons-material/SupervisorAccountOutlined'
import { Alert, Button, Divider, IconButton, ListItemText, ListSubheader, Menu, MenuItem, Snackbar } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate } from 'react-router'
import { api } from '../api/client'
import type { Membership, User } from '../api/types'
import { IMPERSONATION_USERS_KEY, useAuth } from '../auth/AuthContext'

/** Top-bar menu for admins to view the app as another user (testing and support). */
export function ImpersonationMenu() {
  const { t } = useTranslation()
  const { user, impersonate } = useAuth()
  const navigate = useNavigate()
  const [anchor, setAnchor] = useState<HTMLElement | null>(null)
  const [failed, setFailed] = useState(false)
  // Loaded while still the admin; kept in the cache across switches.
  const users = useQuery({
    queryKey: IMPERSONATION_USERS_KEY,
    queryFn: () => api<User[]>('/users'),
    enabled: !!user?.admin && !user.impersonator,
    staleTime: 5 * 60_000,
  })

  if (!user?.canImpersonate) {
    return null
  }

  const choose = async (identifier: string) => {
    setAnchor(null)
    try {
      await impersonate(identifier)
      navigate('/', { replace: true })
    } catch {
      setFailed(true)
    }
  }

  const candidates = (users.data ?? []).filter((u) => u.active && !u.admin && u.id !== user.id)

  return (
    <>
      <IconButton color="inherit" onClick={(e) => setAnchor(e.currentTarget)} title={t('impersonation.menu')} aria-label={t('impersonation.menu')}>
        <SwitchIcon />
      </IconButton>
      <Menu anchorEl={anchor} open={!!anchor} onClose={() => setAnchor(null)} slotProps={{ paper: { sx: { maxWidth: 360 } } }}>
        <ListSubheader sx={{ lineHeight: 1.4, py: 1 }}>
          {t('impersonation.title')}
          <br />
          <small style={{ fontWeight: 400 }}>{t('impersonation.hint')}</small>
        </ListSubheader>
        {user.impersonator && [
          <MenuItem key="exit" onClick={() => choose('_exit')}>
            <ListItemText primary={t('impersonation.back', { admin: user.impersonator.fullName })} />
          </MenuItem>,
          <Divider key="divider" />,
        ]}
        {candidates.map((u) => (
          <MenuItem key={u.id} selected={u.id === user.id} onClick={() => choose(u.email)}>
            <ListItemText primary={u.fullName} secondary={rolesLabel(t, u.memberships)} />
          </MenuItem>
        ))}
      </Menu>
      <Snackbar open={failed} autoHideDuration={4000} onClose={() => setFailed(false)}>
        <Alert severity="error">{t('impersonation.failed')}</Alert>
      </Snackbar>
    </>
  )
}

function rolesLabel(t: (key: string) => string, memberships: Membership[] | undefined): string {
  if (!memberships?.length) {
    return t('impersonation.noRole')
  }

  return memberships.map((m) => `${t(`roles.${m.role}`)} · ${m.projectName}`).join(', ')
}

/** Shown on every page while impersonating, with the way back. */
export function ImpersonationBanner() {
  const { t } = useTranslation()
  const { user, impersonate } = useAuth()
  const navigate = useNavigate()
  if (!user?.impersonator) {
    return null
  }
  const admin = user.impersonator.fullName

  return (
    <Alert
      severity="warning"
      variant="filled"
      sx={{ borderRadius: 0, mb: 2 }}
      action={
        <Button
          color="inherit"
          size="small"
          sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}
          onClick={async () => {
            await impersonate('_exit')
            navigate('/', { replace: true })
          }}
        >
          {t('impersonation.back', { admin })}
        </Button>
      }
    >
      {t('impersonation.banner', { name: user.fullName, admin })}
    </Alert>
  )
}
