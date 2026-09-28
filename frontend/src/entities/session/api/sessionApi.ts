import {api, ApiError, type Schema} from '@/shared/api';

export type CurrentUser = Schema<'CurrentUserOutput'>;
export type Membership = Schema<'MembershipOutput'>;

/** The signed-in user, or null when there is no session. */
export async function fetchCurrentUser(): Promise<CurrentUser | null> {
  try {
    return await api<CurrentUser>('/me');
  } catch (e) {
    if (e instanceof ApiError && e.status === 401) {
      return null;
    }
    throw e;
  }
}

export function login(email: string, password: string): Promise<CurrentUser> {
  return api<CurrentUser>('/login', {method: 'POST', body: {email, password}});
}

export function logout(): Promise<void> {
  return api<void>('/logout', {method: 'POST'});
}

/**
 * "Ver como": the firewall switches the session and redirects to /api/impersonate, which answers with the new
 * current user. `identifier` is an email, or '_exit' to go back.
 */
export function switchUser(identifier: string): Promise<CurrentUser> {
  return api<CurrentUser>(
    `/impersonate?_switch_user=${encodeURIComponent(identifier)}`,
    {method: 'POST'},
  );
}

export function changePassword(
  body: Schema<'ChangePasswordInput'>,
): Promise<void> {
  return api<void>('/me/password', {method: 'POST', body});
}
