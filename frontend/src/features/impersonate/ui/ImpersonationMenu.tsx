import SwitchIcon from '@mui/icons-material/SupervisorAccountOutlined';
import {
  Alert,
  Divider,
  IconButton,
  ListItemText,
  ListSubheader,
  Menu,
  MenuItem,
  Snackbar,
} from '@mui/material';
import {useQuery} from '@tanstack/react-query';
import type {TFunction} from 'i18next';
import {useState} from 'react';
import {useTranslation} from 'react-i18next';
import {useNavigate} from 'react-router';
import {
  SWITCHABLE_USERS_KEY,
  useSession,
  type Membership,
} from '@/entities/session';
import {fetchUsers} from '@/entities/user';

/** Top-bar menu for super admins to view the app as another user (testing and support). */
export function ImpersonationMenu() {
  const {t} = useTranslation();
  const {user, switchUser} = useSession();
  const navigate = useNavigate();
  const [anchor, setAnchor] = useState<HTMLElement | null>(null);
  const [failed, setFailed] = useState(false);
  // Loaded while still the super admin, and kept in the cache across switches.
  const users = useQuery({
    queryKey: SWITCHABLE_USERS_KEY,
    queryFn: fetchUsers,
    enabled: !!user?.superAdmin && !user.impersonator,
    staleTime: 5 * 60_000,
  });

  if (!user?.canImpersonate) {
    return null;
  }

  const choose = async (identifier: string) => {
    setAnchor(null);
    try {
      await switchUser(identifier);
      navigate('/', {replace: true});
    } catch {
      setFailed(true);
    }
  };
  const candidates = (users.data ?? []).filter(
    (u) => u.active && !u.admin && u.id !== user.id,
  );

  return (
    <>
      <IconButton
        color="inherit"
        onClick={(e) => setAnchor(e.currentTarget)}
        title={t('impersonation.menu')}
        aria-label={t('impersonation.menu')}
      >
        <SwitchIcon />
      </IconButton>
      <Menu
        anchorEl={anchor}
        open={!!anchor}
        onClose={() => setAnchor(null)}
        slotProps={{paper: {sx: {maxWidth: 360}}}}
      >
        <ListSubheader sx={{lineHeight: 1.4, py: 1}}>
          {t('impersonation.title')}
          <br />
          <small style={{fontWeight: 400}}>{t('impersonation.hint')}</small>
        </ListSubheader>
        {user.impersonator && [
          <MenuItem key="exit" onClick={() => void choose('_exit')}>
            <ListItemText
              primary={t('impersonation.back', {
                admin: user.impersonator.fullName,
              })}
            />
          </MenuItem>,
          <Divider key="divider" />,
        ]}
        {candidates.map((u) => (
          <MenuItem
            key={u.id}
            selected={u.id === user.id}
            onClick={() => void choose(u.email)}
          >
            <ListItemText
              primary={u.fullName}
              secondary={rolesLabel(t, u.memberships)}
            />
          </MenuItem>
        ))}
      </Menu>
      <Snackbar
        open={failed}
        autoHideDuration={4000}
        onClose={() => setFailed(false)}
      >
        <Alert severity="error">{t('impersonation.failed')}</Alert>
      </Snackbar>
    </>
  );
}

function rolesLabel(t: TFunction, memberships: Membership[]): string {
  if (memberships.length === 0) {
    return t('impersonation.noRole');
  }

  return memberships
    .map((m) => `${t(`roles.${m.role}`)} · ${m.projectName}`)
    .join(', ');
}
