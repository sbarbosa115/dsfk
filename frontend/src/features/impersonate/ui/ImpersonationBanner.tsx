import {Alert, Button} from '@mui/material';
import {useTranslation} from 'react-i18next';
import {useNavigate} from 'react-router';
import {useSession} from '@/entities/session';

/** Shown on every page while viewing as someone, with the way back. */
export function ImpersonationBanner() {
  const {t} = useTranslation();
  const {user, switchUser} = useSession();
  const navigate = useNavigate();
  if (!user?.impersonator) {
    return null;
  }
  const admin = user.impersonator.fullName;

  return (
    <Alert
      severity="warning"
      variant="filled"
      sx={{borderRadius: 0, mb: 2}}
      action={
        <Button
          color="inherit"
          size="small"
          sx={{fontWeight: 700, whiteSpace: 'nowrap'}}
          onClick={async () => {
            await switchUser('_exit');
            navigate('/', {replace: true});
          }}
        >
          {t('impersonation.back', {admin})}
        </Button>
      }
    >
      {t('impersonation.banner', {name: user.fullName, admin})}
    </Alert>
  );
}
