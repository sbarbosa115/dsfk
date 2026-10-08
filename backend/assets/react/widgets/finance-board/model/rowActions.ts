import {isVoidable, type Movement} from '@/entities/finance';

export type MovementRowAction = 'attach' | 'proof' | 'void';

/**
 * What a money movement's row offers (QA-0004): an admin's deposit without its proof asks for it first; one with a
 * proof opens it; voiding is always the menu's last item, worded like everywhere else ("Anular").
 */
export function movementRowActions(
  m: Movement,
  admin: boolean,
): {
  main: MovementRowAction | null;
  items: MovementRowAction[];
  last: MovementRowAction[];
} {
  const attach = admin && m.type === 'DEPOSIT' && !m.voided;
  const proof = (m.attachments ?? []).length > 0;
  const main = attach && !proof ? 'attach' : proof ? 'proof' : null;
  const items = attach && main !== 'attach' ? (['attach'] as const) : [];
  const last = admin && isVoidable(m) ? (['void'] as const) : [];
  return {main, items: [...items], last: [...last]};
}
