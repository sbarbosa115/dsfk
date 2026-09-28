import {Box, Paper, Typography} from '@mui/material';
import {useTranslation} from 'react-i18next';
import {Navigate} from 'react-router';
import {useSession} from '@/entities/session';
import {LoginForm} from '@/features/auth-login';

export function LoginPage() {
  const {t} = useTranslation();
  const {user} = useSession();
  if (user) {
    return <Navigate to="/" replace />;
  }

  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'grid',
        placeItems: 'center',
        p: 2,
        bgcolor: 'background.default',
      }}
    >
      <Paper sx={{p: 4, width: '100%', maxWidth: 400}}>
        <Typography variant="h5" component="h1" gutterBottom>
          {t('app.name')}
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{mb: 3}}>
          {t('login.title')}
        </Typography>
        <LoginForm />
      </Paper>
    </Box>
  );
}
