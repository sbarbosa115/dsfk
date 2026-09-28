import {t} from '@/shared/i18n';
import type {Expense, ExpenseStatus} from '../api/expenseApi';

export function expenseTone(status: ExpenseStatus): string {
  return `expense_${status.toLowerCase()}`;
}

export function expenseStatusLabel(status: ExpenseStatus): string {
  return t(`expenses.status.${status}`);
}

/** "Etapa", "Caja menor" or "Pagado por Carlos Pérez" (out of pocket). */
export function paidFromLabel(expense: Expense): string {
  return expense.paidFrom === 'OUT_OF_POCKET'
    ? t('expenses.outOfPocketBy', {name: expense.paidBy.name})
    : t(`expenses.paidFrom.${expense.paidFrom}`);
}
