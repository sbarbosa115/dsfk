import {t} from '@/shared/i18n';
import type {LedgerEntry, Movement} from '../api/financeApi';

/** Where an entry's money went (or came from): "<stage> · <category>", "Caja menor", "Contingencia". */
export function entryLabel(entry: LedgerEntry): string {
  if (entry.account === 'STAGE') {
    const stage = entry.stageName ?? t('finance.unknownStage');

    return entry.categoryName ? `${stage} · ${entry.categoryName}` : stage;
  }

  return t(`finance.account.${entry.account}`);
}

/** The movement's destinations, the accounts that received money. */
export function destinations(movement: Movement): string {
  return movement.entries
    .filter((e) => !e.amount.startsWith('-'))
    .map(entryLabel)
    .join(', ');
}

/** Voided movements are tinted and say so; the rest have no status. */
export function movementTone(movement: Movement): string | null {
  return movement.voided ? 'movement_voided' : null;
}

/** Only deposits and draws are voided by hand; carry-overs follow from completing a stage. */
export function isVoidable(movement: Movement): boolean {
  return (
    !movement.voided &&
    (movement.type === 'DEPOSIT' || movement.type === 'CONTINGENCY_DRAW')
  );
}
