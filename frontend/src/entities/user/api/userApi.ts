import {api, type Schema} from '@/shared/api';

export type User = Schema<'UserOutput'>;
export type CreateUserBody = Schema<'CreateUserInput'>;
export type UpdateUserBody = Schema<'UpdateUserInput'>;

export const USERS_KEY = ['users'];

export function fetchUsers(): Promise<User[]> {
  return api<User[]>('/users');
}

export function createUser(body: CreateUserBody): Promise<User> {
  return api<User>('/users', {method: 'POST', body});
}

export function updateUser(id: number, body: UpdateUserBody): Promise<User> {
  return api<User>(`/users/${id}`, {method: 'PATCH', body});
}
