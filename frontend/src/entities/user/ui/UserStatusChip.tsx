import {Chip} from '@mui/material';
import {useTranslation} from 'react-i18next';

export function UserStatusChip({active}: {active: boolean}) {
  const {t} = useTranslation();

  return (
    <Chip
      size="small"
      variant={active ? 'filled' : 'outlined'}
      color={active ? 'success' : 'default'}
      label={active ? t('users.active') : t('users.inactive')}
    />
  );
}
