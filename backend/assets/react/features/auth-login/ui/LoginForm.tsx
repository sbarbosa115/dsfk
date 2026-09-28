import {type FormEvent, useState} from 'react';
import {useLocation, useNavigate} from 'react-router';
import {useSession} from '@/entities/session';
import {t} from '@/shared/i18n';
import {errorMessage} from '@/shared/lib/errors';
import {Alert, Button, Field} from '@/shared/ui';

/** Email and password; goes back to where the person was sent from (or home) once signed in. */
export function LoginForm() {
  const {login} = useSession();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const from = (location.state as {from?: string} | null)?.from ?? '/';

  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await login(email, password);
      // Only same-origin paths: `from` comes from our own redirect, never from the URL.
      navigate(from.startsWith('/') && !from.startsWith('//') ? from : '/', {
        replace: true,
      });
    } catch (e) {
      setError(errorMessage(e));
      setBusy(false);
    }
  };

  return (
    <form onSubmit={onSubmit} noValidate>
      <p className="muted">{t('login.subtitle')}</p>
      <Alert kind="error">{error}</Alert>
      <Field label={t('login.email')}>
        <input
          type="email"
          autoComplete="username"
          required
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
      </Field>
      <Field label={t('login.password')}>
        <input
          type="password"
          autoComplete="current-password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
      </Field>
      <Button
        type="submit"
        busy={busy}
        disabled={!email || !password}
        className="btn-block"
      >
        {t('login.submit')}
      </Button>
      <p className="muted small">{t('login.noAccount')}</p>
    </form>
  );
}
