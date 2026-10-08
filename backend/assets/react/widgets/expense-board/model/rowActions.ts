import type {Expense} from '@/entities/expense';

export type ExpenseRowAction =
  'approve' | 'attach' | 'edit' | 'view' | 'receipt' | 'reject' | 'void';

/** A receipt can still be added while the expense is under review, or when it has none. */
export function needsReceipt(e: Expense): boolean {
  return (
    e.attachments.length === 0 ||
    ['SUBMITTED', 'PM_APPROVED', 'REJECTED'].includes(e.status)
  );
}

/**
 * What an expense's row offers, in the house order (QA-0004): the main action is what the row waits on from this
 * person (approve, else the missing receipt, else correcting a rejected one, else opening it); the menu holds the
 * rest of "Este gasto", and rejecting or voiding comes last.
 */
export function expenseRowActions(e: Expense): {
  main: ExpenseRowAction;
  items: ExpenseRowAction[];
  last: ExpenseRowAction[];
} {
  const can: Record<ExpenseRowAction, boolean> = {
    approve: e.permissions.approve,
    attach: e.permissions.attach && needsReceipt(e),
    edit: e.permissions.edit,
    view: true,
    receipt: e.attachments.length > 0,
    reject: e.permissions.reject,
    void: e.permissions.void,
  };
  const main =
    (['approve', 'attach', 'edit'] as const).find((a) => can[a]) ?? 'view';
  const items = (['view', 'edit', 'attach', 'receipt'] as const).filter(
    (a) => a !== main && can[a],
  );
  const last = (['reject', 'void'] as const).filter((a) => can[a]);
  return {main, items, last};
}
