import {Alert, Button, Stack, TextField} from '@mui/material';
import {useState, type FormEvent} from 'react';
import {useTranslation} from 'react-i18next';
import {useLocation, useNavigate} from 'react-router';
import {useSession} from '@/entities/session';
import {errorMessage} from '@/shared/lib/errors';

/** Email and password; goes back to where the user was sent from (or home) once signed in. */
export function LoginForm() {
  const {t} = useTranslation();
  const {login} = useSession();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const from = (location.state as {from?: string} | null)?.from ?? '/';

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await login(email, password);
      // Only same-origin paths: `from` comes from our own redirect, never from the URL.
      navigate(from.startsWith('/') && !from.startsWith('//') ? from : '/', {
        replace: true,
      });
    } catch (e) {
      setError(errorMessage(t, e));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Stack component="form" spacing={2} onSubmit={handleSubmit} noValidate>
      {error && <Alert severity="error">{error}</Alert>}
      <TextField
        label={t('login.email')}
        type="email"
        autoComplete="username"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        required
        autoFocus
      />
      <TextField
        label={t('login.password')}
        type="password"
        autoComplete="current-password"
        value={password}
        onChange={(e) => setPassword(e.target.value)}
        required
      />
      <Button
        type="submit"
        variant="contained"
        size="large"
        disabled={submitting || !email || !password}
      >
        {t('login.submit')}
      </Button>
    </Stack>
  );
}
