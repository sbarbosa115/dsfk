import {api, type Schema} from '@/shared/api';

export type PettyCash = Schema<'PettyCashOutput'>;
export type Cycle = Schema<'CycleOutput'>;
export type CycleMovement = NonNullable<Cycle['movements']>[number];

export function pettyCashKey(projectId: number | string): readonly unknown[] {
  return ['petty-cash', String(projectId)];
}

export function fetchPettyCash(projectId: number | string): Promise<PettyCash> {
  return api<PettyCash>(`/projects/${projectId}/petty-cash`);
}

export function cycleKey(cycleId: number): readonly unknown[] {
  return ['petty-cash-cycle', cycleId];
}

export function fetchCycle(cycleId: number): Promise<Cycle> {
  return api<Cycle>(`/petty-cash-cycles/${cycleId}`);
}

export function cycleTone(status: Cycle['status']): string {
  return `cycle_${status.toLowerCase()}`;
}
