import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {
  AUDIT_KEY,
  AUDIT_PATH,
  changedFields,
  type AuditEntry,
  type AuditPage,
} from '@/entities/audit';
import {api} from '@/shared/api';
import {t} from '@/shared/i18n';
import {formatDateTime} from '@/shared/lib/format';
import {useList} from '@/shared/lib/list';
import {
  Actions,
  DataTable,
  FilterBar,
  ListView,
  Modal,
  PageHeader,
  RowActions,
} from '@/shared/ui';

type Filters = {q: string; projectId: string; entityType: string};

function value(v: unknown): string {
  if (v === null || v === undefined || v === '') {
    return '—';
  }

  return typeof v === 'object' ? JSON.stringify(v) : String(v);
}

function kind(entityType: string): string {
  return t(`audit.entity.${entityType}`, {defaultValue: entityType});
}

/** Who changed what and when, across every project. Admins only. */
export function AuditLog() {
  const list = useList<AuditEntry, Filters>(AUDIT_KEY, AUDIT_PATH, {
    q: '',
    projectId: '',
    entityType: '',
  });
  const projects = useQuery({
    queryKey: ['projects', 'audit-filter'],
    queryFn: () =>
      api<{items: Array<{id: number; name: string}>}>('/projects?perPage=100'),
  });
  const [open, setOpen] = useState<AuditEntry | null>(null);
  const types = (list.data as AuditPage | null)?.entityTypes ?? [];

  return (
    <>
      <PageHeader title={t('audit.title')} subtitle={t('audit.subtitle')} />
      <FilterBar
        search={list.filters.q}
        onSearch={(q) => list.update({q})}
        searchPlaceholder={t('audit.search')}
        filters={[
          {
            name: 'projectId',
            label: t('audit.project'),
            value: list.filters.projectId,
            onChange: (projectId) => list.update({projectId}),
            options: [
              {value: '', label: t('common.all')},
              ...(projects.data?.items ?? []).map((p) => ({
                value: String(p.id),
                label: p.name,
              })),
            ],
          },
          {
            name: 'entityType',
            label: t('audit.record'),
            value: list.filters.entityType,
            onChange: (entityType) => list.update({entityType}),
            options: [
              {value: '', label: t('common.all')},
              ...types.map((type) => ({value: type, label: kind(type)})),
            ],
          },
        ]}
      />
      <ListView
        list={list}
        showAll={{projectId: '', entityType: ''}}
        empty={t('audit.noMatches')}
        emptyAll={t('audit.none')}
        columns={[
          t('audit.when'),
          t('audit.who'),
          t('audit.project'),
          t('audit.record'),
          t('audit.what'),
        ]}
        renderRow={(entry) => {
          const fields = changedFields(entry);

          return (
            <tr key={entry.id}>
              <td>{formatDateTime(entry.createdAt)}</td>
              <td>{entry.user ?? t('audit.system')}</td>
              <td>{entry.projectName ?? '—'}</td>
              <td>
                {kind(entry.entityType)}
                {entry.entityId !== null && entry.entityId !== undefined && (
                  <span className="muted"> #{entry.entityId}</span>
                )}
              </td>
              <td>
                <strong>{t(`audit.action.${entry.action}`)}</strong>
                {fields.length > 0 && (
                  <div className="small muted">
                    {fields
                      .slice(0, 3)
                      .map((f) => f.field)
                      .join(', ')}
                    {fields.length > 3 && ` +${fields.length - 3}`}
                  </div>
                )}
              </td>
              <Actions>
                <RowActions
                  name={t('audit.view')}
                  view={
                    fields.length > 0 && {
                      label: t('audit.view'),
                      onClick: () => setOpen(entry),
                    }
                  }
                />
              </Actions>
            </tr>
          );
        }}
      />
      {open && (
        <Modal
          title={`${kind(open.entityType)} #${open.entityId ?? ''} · ${t(`audit.action.${open.action}`)}`}
          size="wide"
          onClose={() => setOpen(null)}
        >
          <p className="muted small">
            {open.user ?? t('audit.system')} · {formatDateTime(open.createdAt)}
          </p>
          <DataTable
            columns={[t('audit.field'), t('audit.before'), t('audit.after')]}
            rows={changedFields(open)}
            actions={false}
            renderRow={(f) => (
              <tr key={f.field}>
                <td>
                  <code>{f.field}</code>
                </td>
                <td>{value(f.before)}</td>
                <td>{value(f.after)}</td>
              </tr>
            )}
          />
        </Modal>
      )}
    </>
  );
}
