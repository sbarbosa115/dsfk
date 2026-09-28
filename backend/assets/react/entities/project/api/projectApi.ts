import {api, type Schema} from '@/shared/api';

export type Project = Schema<'ProjectOutput'>;
export type ProjectStatus = Project['status'];
export type ProjectRole = Schema<'MemberOutput'>['role'];
export type CreateProjectBody = Schema<'CreateProjectInput'>;
export type UpdateProjectBody = Partial<Schema<'UpdateProjectInput'>>;

export const PROJECT_STATUSES: readonly ProjectStatus[] = [
  'DRAFT',
  'ACTIVE',
  'COMPLETED',
  'ARCHIVED',
];

export const PROJECTS_KEY = ['projects'];

export function projectKey(id: number | string): readonly unknown[] {
  return ['project', String(id)];
}

export function fetchProject(id: number | string): Promise<Project> {
  return api<Project>(`/projects/${id}`);
}

export function createProject(
  body: Partial<CreateProjectBody>,
): Promise<Project> {
  return api<Project>('/projects', {method: 'POST', body});
}

export function updateProject(
  id: number,
  body: UpdateProjectBody,
): Promise<Project> {
  return api<Project>(`/projects/${id}`, {method: 'PATCH', body});
}

export function assignMember(
  projectId: number,
  userId: number,
  role: ProjectRole,
): Promise<Project> {
  return api<Project>(`/projects/${projectId}/members`, {
    method: 'POST',
    body: {userId, role},
  });
}

export function removeMember(
  projectId: number,
  memberId: number,
): Promise<void> {
  return api<void>(`/projects/${projectId}/members/${memberId}`, {
    method: 'DELETE',
  });
}
