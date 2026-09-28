import {useNavigate} from 'react-router';
import {useSession} from '@/entities/session';
import {t} from '@/shared/i18n';
import {Button} from '@/shared/ui';

/** Shown on every page while viewing as someone, with the way back. */
export function ImpersonationBanner() {
  const {user, switchUser} = useSession();
  const navigate = useNavigate();
  if (!user?.impersonator) {
    return null;
  }
  const admin = user.impersonator.fullName;

  return (
    <div className="impersonation-banner" role="status">
      <span>{t('impersonation.banner', {name: user.fullName, admin})}</span>
      <Button
        variant="ghost"
        size="sm"
        onClick={async () => {
          await switchUser('_exit');
          navigate('/', {replace: true});
        }}
      >
        {t('impersonation.back', {admin})}
      </Button>
    </div>
  );
}
