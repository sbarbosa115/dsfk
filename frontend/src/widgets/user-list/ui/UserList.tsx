import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/EditOutlined';
import {Button, IconButton, Stack} from '@mui/material';
import {useQuery} from '@tanstack/react-query';
import {useState} from 'react';
import {useTranslation} from 'react-i18next';
import {useSession} from '@/entities/session';
import {
  fetchUsers,
  UserAccessChip,
  UserStatusChip,
  USERS_KEY,
  type User,
} from '@/entities/user';
import {UserDialog} from '@/features/user-edit';
import {DataTable} from '@/shared/ui/DataTable';
import {PageHeader} from '@/shared/ui/PageHeader';
import {QueryState} from '@/shared/ui/QueryState';

/** Every user, with create and edit. */
export function UserList() {
  const {t} = useTranslation();
  const {user: me} = useSession();
  const users = useQuery({queryKey: USERS_KEY, queryFn: fetchUsers});
  // undefined = closed, null = creating, a user = editing
  const [editing, setEditing] = useState<User | null | undefined>(undefined);

  return (
    <>
      <PageHeader
        title={t('users.title')}
        subtitle={t('users.subtitle')}
        action={
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => setEditing(null)}
          >
            {t('users.new')}
          </Button>
        }
      />
      <QueryState query={users}>
        {(rows) => (
          <DataTable
            label={t('users.title')}
            rows={rows}
            rowKey={(u) => u.id}
            columns={[
              {
                key: 'name',
                header: t('users.fullName'),
                render: (u) => (
                  <Stack
                    direction="row"
                    spacing={1}
                    sx={{alignItems: 'center'}}
                  >
                    <span>{u.fullName}</span>
                    <UserAccessChip user={u} />
                  </Stack>
                ),
              },
              {
                key: 'email',
                header: t('users.email'),
                render: (u) => u.email,
                hideOnMobile: true,
              },
              {
                key: 'projects',
                header: t('users.projects'),
                hideOnMobile: true,
                render: (u) =>
                  u.memberships
                    .map((m) => `${m.projectName} (${t(`roles.${m.role}`)})`)
                    .join(', ') || '—',
              },
              {
                key: 'status',
                header: t('users.status'),
                render: (u) => <UserStatusChip active={u.active} />,
              },
              {
                key: 'actions',
                header: '',
                align: 'right',
                render: (u) => (
                  <IconButton
                    aria-label={t('users.editNamed', {name: u.fullName})}
                    title={
                      u.superAdmin && !me?.superAdmin
                        ? t('errors.super_admin_required')
                        : undefined
                    }
                    // Only a super admin edits another super admin.
                    disabled={
                      u.superAdmin && !me?.superAdmin && u.id !== me?.id
                    }
                    onClick={() => setEditing(u)}
                  >
                    <EditIcon />
                  </IconButton>
                ),
              },
            ]}
          />
        )}
      </QueryState>
      {editing !== undefined && (
        <UserDialog user={editing} onClose={() => setEditing(undefined)} />
      )}
    </>
  );
}
