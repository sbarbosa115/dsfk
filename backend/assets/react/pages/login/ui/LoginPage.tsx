import {Navigate} from 'react-router';
import {useSession} from '@/entities/session';
import {LoginForm} from '@/features/auth-login';
import {t} from '@/shared/i18n';

export function LoginPage() {
  const {user} = useSession();
  if (user) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="auth-page">
      <div className="auth-card">
        <div className="auth-brand">
          <span className="brand-name">{t('app.name')}</span>
          <p className="muted small">{t('app.tagline')}</p>
        </div>
        <h1>{t('login.title')}</h1>
        <LoginForm />
      </div>
    </div>
  );
}
