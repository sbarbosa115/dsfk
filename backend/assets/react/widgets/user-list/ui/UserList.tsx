import {useState} from 'react';
import {useSession} from '@/entities/session';
import {USERS_KEY, UserAccess, type User} from '@/entities/user';
import {UserDialog} from '@/features/user-edit';
import {ToggleActiveButton} from '@/features/user-toggle-active';
import {t} from '@/shared/i18n';
import {useList} from '@/shared/lib/list';
import {
  ActionButton,
  Actions,
  Alert,
  FilterBar,
  IconButton,
  ListView,
  PageHeader,
  Row,
  RowLegend,
} from '@/shared/ui';

/** Every user: search, active/inactive filter, create, edit, disable. */
export function UserList() {
  const {user: me} = useSession();
  const list = useList<User>(USERS_KEY, '/users', {q: '', status: 'active'});
  // undefined = closed, null = creating, a user = editing
  const [editing, setEditing] = useState<User | null | undefined>(undefined);
  const [notice, setNotice] = useState<string | null>(null);
  const newUser = (
    <ActionButton
      action="setup"
      main
      size="md"
      icon="plus"
      onClick={() => setEditing(null)}
    >
      {t('users.new')}
    </ActionButton>
  );

  return (
    <>
      <PageHeader title={t('users.title')} subtitle={t('users.subtitle')} />
      <FilterBar
        search={list.filters.q}
        onSearch={(q) => list.update({q})}
        searchPlaceholder={t('users.searchPlaceholder')}
        filters={[
          {
            name: 'status',
            label: t('common.status'),
            value: list.filters.status,
            onChange: (status) => list.update({status}),
            options: [
              {value: 'active', label: t('common.onlyActive')},
              {value: 'inactive', label: t('common.inactive')},
              {value: 'all', label: t('common.all')},
            ],
          },
        ]}
      >
        {newUser}
      </FilterBar>
      <Alert kind="success" onDismiss={() => setNotice(null)}>
        {notice}
      </Alert>
      <RowLegend
        statuses={[
          {value: 'active', label: t('common.active')},
          {value: 'inactive', label: t('common.inactive')},
        ]}
      />
      <ListView
        list={list}
        empty={t('users.empty')}
        showAll={{status: 'all'}}
        emptyAll={t('users.emptyAll')}
        emptyAction={newUser}
        columns={[t('users.fullName'), t('users.email'), t('users.projects')]}
        renderRow={(user) => {
          // Only a super admin edits another super admin.
          const locked =
            user.superAdmin && !me?.superAdmin && user.id !== me?.id;

          return (
            <Row
              key={user.id}
              status={user.active ? 'active' : 'inactive'}
              label={user.active ? t('common.active') : t('common.inactive')}
              muted={!user.active}
            >
              <td className="strong">
                {user.fullName} <UserAccess user={user} />
              </td>
              <td>{user.email}</td>
              <td className="small">
                {user.memberships.length === 0 ? (
                  <span className="muted">{t('users.noProjects')}</span>
                ) : (
                  user.memberships.map((m) => (
                    <div key={m.projectId}>
                      {m.projectName}{' '}
                      <span className="muted">· {t(`roles.${m.role}`)}</span>
                    </div>
                  ))
                )}
              </td>
              <Actions>
                <IconButton
                  icon="pencil"
                  label={
                    locked ? t('users.lockedSuperAdmin') : t('common.edit')
                  }
                  disabled={locked}
                  onClick={() => setEditing(user)}
                />
                <ToggleActiveButton
                  user={user}
                  disabled={locked || user.id === me?.id}
                  onDone={setNotice}
                />
              </Actions>
            </Row>
          );
        }}
      />
      {editing !== undefined && (
        <UserDialog user={editing} onClose={() => setEditing(undefined)} />
      )}
    </>
  );
}
