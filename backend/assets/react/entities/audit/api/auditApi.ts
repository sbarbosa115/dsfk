import type {Schema} from '@/shared/api';

export type AuditEntry = Schema<'AuditEntryOutput'>;
export type AuditPage = Schema<'AuditPageOutput'>;

export const AUDIT_KEY = ['audit'];
export const AUDIT_PATH = '/audit';

/** A change's before and after, per field. */
export function changedFields(
  entry: AuditEntry,
): Array<{field: string; before: unknown; after: unknown}> {
  return Object.entries(entry.changes ?? {}).map(([field, pair]) => {
    const [before, after] = Array.isArray(pair) ? pair : [undefined, pair];

    return {field, before, after};
  });
}
