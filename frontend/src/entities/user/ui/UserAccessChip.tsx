import {Chip} from '@mui/material';
import {useTranslation} from 'react-i18next';
import type {User} from '../api/userApi';

/** "Administrador" / "Super administrador", or nothing for ordinary users. */
export function UserAccessChip({
  user,
}: {
  user: Pick<User, 'admin' | 'superAdmin'>;
}) {
  const {t} = useTranslation();
  if (!user.admin) {
    return null;
  }

  return (
    <Chip
      size="small"
      color="primary"
      label={user.superAdmin ? t('roles.SUPER_ADMIN') : t('roles.ADMIN')}
    />
  );
}
