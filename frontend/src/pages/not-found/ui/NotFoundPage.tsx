import {Button, Stack, Typography} from '@mui/material';
import {useTranslation} from 'react-i18next';
import {Link} from 'react-router';

export function NotFoundPage() {
  const {t} = useTranslation();

  return (
    <Stack spacing={2} sx={{alignItems: 'flex-start'}}>
      <Typography variant="h5" component="h1">
        {t('common.notFound')}
      </Typography>
      <Button component={Link} to="/" variant="outlined">
        {t('common.home')}
      </Button>
    </Stack>
  );
}
