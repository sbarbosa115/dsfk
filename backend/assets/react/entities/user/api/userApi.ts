import {api, type Schema} from '@/shared/api';

export type User = Schema<'UserOutput'>;
export type CreateUserBody = Schema<'CreateUserInput'>;
export type UpdateUserBody = Schema<'UpdateUserInput'>;

export const USERS_KEY = ['users'];

export function createUser(body: CreateUserBody): Promise<User> {
  return api<User>('/users', {method: 'POST', body});
}

export function updateUser(
  id: number,
  body: Partial<UpdateUserBody>,
): Promise<User> {
  return api<User>(`/users/${id}`, {method: 'PATCH', body});
}

/** The active users "Ver como" can switch to (every page of them). */
export async function fetchSwitchableUsers(): Promise<User[]> {
  const page = await api<Schema<'UserPageOutput'>>('/users?perPage=200');

  return page.items.filter((u) => u.active && !u.admin);
}
