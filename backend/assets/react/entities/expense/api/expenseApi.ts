import type {QueryClient} from '@tanstack/react-query';
import {api, type Schema, upload} from '@/shared/api';

export type Expense = Schema<'ExpenseOutput'>;
export type ExpensePage = Schema<'ExpensePageOutput'>;
export type ExpenseSummary = ExpensePage['summary'];
export type ExpenseStatus = Expense['status'];
export type PaidFrom = Expense['paidFrom'];
export type ExpenseEvent = NonNullable<Expense['events']>[number];

export const EXPENSE_STATUSES: readonly ExpenseStatus[] = [
  'SUBMITTED',
  'PM_APPROVED',
  'APPROVED',
  'REJECTED',
  'REIMBURSED',
  'VOIDED',
];

export interface ExpenseBody {
  stageId: number | null;
  categoryId: number | null;
  date: string;
  amount: string;
  description: string;
  supplier: string | null;
  invoiceNumber: string | null;
  paidFrom?: PaidFrom | null;
}

/** Every page of a project's expenses (useList adds its filters and page). */
export function expensesKey(projectId: number | string): readonly unknown[] {
  return ['expenses', String(projectId)];
}

export function expensesPath(projectId: number | string): string {
  return `/projects/${projectId}/expenses`;
}

export function expenseKey(expenseId: number): readonly unknown[] {
  return ['expense', expenseId];
}

export function fetchExpense(expenseId: number): Promise<Expense> {
  return api<Expense>(`/expenses/${expenseId}`);
}

/** Every expense write: `path` under /api. Most answer with the expense. */
export function sendExpense<T = Expense>(
  path: string,
  method: 'POST' | 'PUT',
  body?: unknown,
): Promise<T> {
  return api<T>(path, {method, body});
}

export function uploadReceipt(expenseId: number, file: File): Promise<Expense> {
  return upload<Expense>(`/expenses/${expenseId}/attachments`, file);
}

/**
 * An expense moves money and budget figures: its list, its detail, the project's finance, movements, caja menor
 * and plan are reloaded.
 */
export function refreshExpenses(
  queryClient: QueryClient,
  projectId: number | string,
): void {
  for (const key of [
    'expenses',
    'expense',
    'finance',
    'movements',
    'petty-cash',
  ]) {
    void queryClient.invalidateQueries({
      queryKey: key === 'expense' ? [key] : [key, String(projectId)],
    });
  }
}
