import type {QueryClient} from '@tanstack/react-query';
import {api, type Schema, upload} from '@/shared/api';

export type Finance = Schema<'FinanceOutput'>;
export type StageFunding = Finance['stages'][number];
export type CategorySpending = Finance['categories'][number];
export type Movement = Schema<'MovementOutput'>;
export type LedgerEntry = Movement['entries'][number];
export type MovementType = Movement['type'];
export type LedgerAccount = LedgerEntry['account'];
export type PaymentMethod = NonNullable<Movement['method']>;

export const PAYMENT_METHODS: readonly PaymentMethod[] = [
  'TRANSFER',
  'CASH',
  'CHECK',
  'OTHER',
];

export function financeKey(projectId: number | string): readonly unknown[] {
  return ['finance', String(projectId)];
}

/** Every page of the movements list (useList adds its filters and page). */
export function movementsKey(projectId: number | string): readonly unknown[] {
  return ['movements', String(projectId)];
}

export function movementsPath(projectId: number | string): string {
  return `/projects/${projectId}/movements`;
}

export function fetchFinance(projectId: number | string): Promise<Finance> {
  return api<Finance>(`/projects/${projectId}/finance`);
}

/** A money write: `path` is under /api, e.g. `/projects/9/deposits`. */
export function postFinance<T>(path: string, body: unknown): Promise<T> {
  return api<T>(path, {method: 'POST', body});
}

export function uploadProof(movementId: number, file: File): Promise<Movement> {
  return upload<Movement>(`/movements/${movementId}/attachments`, file);
}

/** Where a proof opens: the API serves it inline, in a new tab. */
export function attachmentUrl(attachmentId: number): string {
  return `/api/attachments/${attachmentId}`;
}

/**
 * After money moved, the summary, the movements and the plan (a completed stage changes its status) are
 * reloaded.
 */
export function refreshFinance(
  queryClient: QueryClient,
  projectId: number | string,
): void {
  void queryClient.invalidateQueries({queryKey: financeKey(projectId)});
  void queryClient.invalidateQueries({queryKey: movementsKey(projectId)});
  void queryClient.invalidateQueries({queryKey: ['plan', String(projectId)]});
}
