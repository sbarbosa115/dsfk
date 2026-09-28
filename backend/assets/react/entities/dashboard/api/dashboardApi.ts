import {api, type Schema} from '@/shared/api';

export type ProjectDashboard = Schema<'ProjectDashboardOutput'>;
export type StageHealth = ProjectDashboard['stages'][number];
export type DashboardAlert = ProjectDashboard['alerts'][number];
export type PortfolioRow = Schema<'PortfolioRowOutput'>;

export const PORTFOLIO_KEY = ['portfolio'];

export function dashboardKey(projectId: number | string): readonly unknown[] {
  return ['dashboard', String(projectId)];
}

export function fetchDashboard(
  projectId: number | string,
): Promise<ProjectDashboard> {
  return api<ProjectDashboard>(`/projects/${projectId}/dashboard`);
}

export function fetchPortfolio(): Promise<PortfolioRow[]> {
  return api<PortfolioRow[]>('/dashboard');
}
