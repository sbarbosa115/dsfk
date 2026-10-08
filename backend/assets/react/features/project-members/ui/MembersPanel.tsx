import {useQuery, useQueryClient} from '@tanstack/react-query';
import {useState} from 'react';
import {SESSION_KEY} from '@/entities/session';
import {
  assignMember,
  projectKey,
  removeMember,
  type Project,
  type ProjectRole,
} from '@/entities/project';
import {fetchSwitchableUsers} from '@/entities/user';
import {t} from '@/shared/i18n';
import {errorMessage} from '@/shared/lib/errors';
import {
  ActionButton,
  Actions,
  Alert,
  ConfirmModal,
  DataTable,
  EmptyState,
  Row,
  RowLegend,
  Select,
} from '@/shared/ui';

type Member = Project['members'][number];

const ROLES: readonly ProjectRole[] = ['PROJECT_MANAGER', 'TEAM_LEAD'];

/**
 * Who works on a project. Admins add people (active, non-admin users) with a role, change it, or remove them;
 * everyone else only sees the list.
 */
export function MembersPanel({
  project,
  canManage,
}: {
  project: Project;
  canManage: boolean;
}) {
  const queryClient = useQueryClient();
  const users = useQuery({
    queryKey: ['assignable-users'],
    queryFn: fetchSwitchableUsers,
    enabled: canManage,
  });
  const [userId, setUserId] = useState('');
  const [role, setRole] = useState<ProjectRole>('TEAM_LEAD');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [removing, setRemoving] = useState<Member | null>(null);

  const refresh = (updated?: Project) => {
    if (updated) {
      queryClient.setQueryData(projectKey(project.id), updated);
    } else {
      void queryClient.invalidateQueries({queryKey: projectKey(project.id)});
    }
    void queryClient.invalidateQueries({queryKey: SESSION_KEY});
  };

  const add = async (id: number, newRole: ProjectRole, name: string) => {
    setBusy(true);
    setError(null);
    setNotice(null);
    try {
      refresh(await assignMember(project.id, id, newRole));
      setUserId('');
      setNotice(t('members.added', {name, role: t(`roles.${newRole}`)}));
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  const remove = async (member: Member) => {
    setBusy(true);
    setError(null);
    try {
      await removeMember(project.id, member.id);
      refresh();
      setRemoving(null);
      setNotice(t('members.removed', {name: member.user.fullName}));
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  const assignable = (users.data ?? []).filter(
    (u) => !project.members.some((m) => m.user.id === u.id),
  );
  const chosen = assignable.find((u) => String(u.id) === userId);

  return (
    <section className="card">
      <div className="card-header">
        <h2>{t('members.title')}</h2>
      </div>
      <p className="muted small">{t('members.intro')}</p>
      <Alert kind="success" onDismiss={() => setNotice(null)}>
        {notice}
      </Alert>
      <Alert kind="error" onDismiss={() => setError(null)}>
        {!removing ? error : null}
      </Alert>
      {canManage && (
        <div className="toolbar">
          <Select
            label={t('members.person')}
            value={userId}
            onChange={setUserId}
            options={[
              {value: '', label: t('members.choosePerson')},
              ...assignable.map((u) => ({
                value: String(u.id),
                label: `${u.fullName} (${u.email})`,
              })),
            ]}
          />
          <Select
            label={t('members.role')}
            value={role}
            onChange={(value) => setRole(value as ProjectRole)}
            options={ROLES.map((r) => ({value: r, label: t(`roles.${r}`)}))}
          />
          <ActionButton
            action="setup"
            main
            size="md"
            busy={busy && !removing}
            disabled={!chosen}
            onClick={() => chosen && void add(chosen.id, role, chosen.fullName)}
          >
            {t('members.add')}
          </ActionButton>
        </div>
      )}
      {project.members.length === 0 ? (
        <EmptyState>{t('members.empty')}</EmptyState>
      ) : (
        <>
          <RowLegend
            statuses={ROLES.map((r) => ({value: r, label: t(`roles.${r}`)}))}
          />
          <DataTable
            columns={[t('members.person'), t('users.email'), t('members.role')]}
            actions={canManage}
            rows={project.members}
            renderRow={(member) => (
              <Row
                key={member.id}
                status={member.role}
                label={t(`roles.${member.role}`)}
              >
                <td className="strong">{member.user.fullName}</td>
                <td>{member.user.email}</td>
                <td>
                  {canManage ? (
                    <select
                      className="row-action-select"
                      aria-label={`${t('members.role')}: ${member.user.fullName}`}
                      value={member.role}
                      onChange={(event) =>
                        void add(
                          member.user.id,
                          event.target.value as ProjectRole,
                          member.user.fullName,
                        )
                      }
                    >
                      {ROLES.map((r) => (
                        <option key={r} value={r}>
                          {t(`roles.${r}`)}
                        </option>
                      ))}
                    </select>
                  ) : (
                    t(`roles.${member.role}`)
                  )}
                </td>
                {canManage && (
                  <Actions>
                    <ActionButton
                      action="danger"
                      onClick={() => setRemoving(member)}
                    >
                      {t('members.remove')}
                    </ActionButton>
                  </Actions>
                )}
              </Row>
            )}
          />
        </>
      )}
      {removing && (
        <ConfirmModal
          title={t('members.remove')}
          confirmLabel={t('members.remove')}
          busy={busy}
          error={error}
          onConfirm={() => void remove(removing)}
          onClose={() => {
            setRemoving(null);
            setError(null);
          }}
        >
          {t('members.confirmRemove', {name: removing.user.fullName})}
        </ConfirmModal>
      )}
    </section>
  );
}
