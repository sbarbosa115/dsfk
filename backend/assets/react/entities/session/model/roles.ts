import type {CurrentUser} from '../api/sessionApi';

/** Admins and Project Managers manage money and see dashboards; Team Leads do not. */
export function isManager(user: CurrentUser | null): boolean {
  return (
    !!user &&
    (user.admin || user.memberships.some((m) => m.role === 'PROJECT_MANAGER'))
  );
}
