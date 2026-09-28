import {t} from '@/shared/i18n';
import {Badge} from '@/shared/ui';
import type {User} from '../api/userApi';

/** "Administrador" / "Super administrador" next to a name; nothing for everyone else. */
export function UserAccess({user}: {user: Pick<User, 'admin' | 'superAdmin'>}) {
  if (!user.admin) {
    return null;
  }

  return (
    <Badge value="info">
      {user.superAdmin ? t('roles.SUPER_ADMIN') : t('roles.ADMIN')}
    </Badge>
  );
}
