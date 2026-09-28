import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {useNavigate} from 'react-router';
import {SWITCHABLE_USERS_KEY, useSession} from '@/entities/session';
import {fetchSwitchableUsers} from '@/entities/user';
import {t} from '@/shared/i18n';

/**
 * "Ver como", in the sidebar of a super admin: pick an active, non-admin user to view the app as them, or "Nadie"
 * to come back. Each option says the person's roles, so the right PM or Team Lead is easy to find.
 */
export function ImpersonationPicker() {
  const {user, switchUser} = useSession();
  const navigate = useNavigate();
  const [failed, setFailed] = useState(false);
  // Loaded while still the super admin, and kept across switches.
  const users = useQuery({
    queryKey: SWITCHABLE_USERS_KEY,
    queryFn: fetchSwitchableUsers,
    enabled: !!user?.canImpersonate && !user.impersonator,
    staleTime: 5 * 60_000,
  });
  if (!user?.canImpersonate) {
    return null;
  }

  const choose = async (identifier: string) => {
    setFailed(false);
    try {
      await switchUser(identifier);
      navigate('/', {replace: true});
    } catch {
      setFailed(true);
    }
  };
  const current = user.impersonator ? user.email : '';

  return (
    <div className="impersonation-picker">
      <label className="nav-section-title" htmlFor="impersonation-select">
        {t('impersonation.label')}
      </label>
      <select
        id="impersonation-select"
        value={current}
        title={t('impersonation.hint')}
        onChange={(event) => void choose(event.target.value || '_exit')}
      >
        <option value="">{t('impersonation.none')}</option>
        {/* Keep the current choice visible while the list loads. */}
        {user.impersonator &&
          !(users.data ?? []).some((u) => u.email === user.email) && (
            <option value={user.email}>{user.fullName}</option>
          )}
        {(users.data ?? []).map((u) => (
          <option key={u.id} value={u.email}>
            {`${u.fullName} · ${
              u.memberships.length === 0
                ? t('impersonation.noRole')
                : u.memberships
                    .map((m) => `${t(`roles.${m.role}`)} ${m.projectName}`)
                    .join(', ')
            }`}
          </option>
        ))}
      </select>
      {failed && (
        <span className="field-error">{t('impersonation.failed')}</span>
      )}
    </div>
  );
}
