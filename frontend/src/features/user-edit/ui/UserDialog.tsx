import {FormControlLabel, Switch, TextField, Typography} from '@mui/material';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useState} from 'react';
import {useTranslation} from 'react-i18next';
import {useSession} from '@/entities/session';
import {createUser, updateUser, USERS_KEY, type User} from '@/entities/user';
import {fieldError} from '@/shared/lib/errors';
import {FormDialog} from '@/shared/ui/FormDialog';

/** Creates a user (user = null) or edits one. Admins cannot lock themselves out. */
export function UserDialog({
  user,
  onClose,
}: {
  user: User | null;
  onClose: () => void;
}) {
  const {t} = useTranslation();
  const {user: me} = useSession();
  const queryClient = useQueryClient();
  const isSelf = user?.id === me?.id;
  const [form, setForm] = useState({
    fullName: user?.fullName ?? '',
    email: user?.email ?? '',
    password: '',
    admin: user?.admin ?? false,
    superAdmin: user?.superAdmin ?? false,
    active: user?.active ?? true,
  });

  const save = useMutation({
    mutationFn: () => {
      const {active, password, ...rest} = form;

      return user
        ? updateUser(user.id, {
            ...rest,
            active,
            password: password || null,
          })
        : createUser({...rest, password});
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({queryKey: USERS_KEY});
      onClose();
    },
  });

  const text = (field: 'fullName' | 'email' | 'password') => ({
    value: form[field],
    onChange: (e: {target: {value: string}}) =>
      setForm((f) => ({...f, [field]: e.target.value})),
    error: !!fieldError(save.error, field),
    helperText: fieldError(save.error, field),
  });

  return (
    <FormDialog
      title={user ? t('users.edit') : t('users.new')}
      submitLabel={user ? t('common.save') : t('common.create')}
      error={save.error}
      pending={save.isPending}
      onSubmit={() => save.mutate()}
      onClose={onClose}
      maxWidth="xs"
    >
      <TextField
        label={t('users.fullName')}
        required
        autoFocus
        {...text('fullName')}
      />
      <TextField
        label={t('users.email')}
        type="email"
        required
        {...text('email')}
      />
      <TextField
        label={user ? t('users.newPassword') : t('users.password')}
        type="password"
        autoComplete="new-password"
        required={!user}
        {...text('password')}
      />
      <FormControlLabel
        control={
          <Switch
            checked={form.admin}
            disabled={isSelf}
            onChange={(e) =>
              setForm((f) => ({
                ...f,
                admin: e.target.checked,
                superAdmin: e.target.checked && f.superAdmin,
              }))
            }
          />
        }
        label={t('users.admin')}
      />
      {/* Granting "Ver como" is itself a super admin power, so ordinary admins never see this. */}
      {me?.superAdmin && (
        <FormControlLabel
          sx={{alignItems: 'flex-start'}}
          control={
            <Switch
              checked={form.superAdmin}
              disabled={isSelf}
              onChange={(e) =>
                setForm((f) => ({
                  ...f,
                  superAdmin: e.target.checked,
                  admin: e.target.checked || f.admin,
                }))
              }
            />
          }
          label={
            <>
              {t('users.superAdmin')}
              <Typography
                variant="caption"
                color="text.secondary"
                sx={{display: 'block'}}
              >
                {t('users.superAdminHint')}
              </Typography>
            </>
          }
        />
      )}
      {user && (
        <FormControlLabel
          control={
            <Switch
              checked={form.active}
              disabled={isSelf}
              onChange={(e) =>
                setForm((f) => ({...f, active: e.target.checked}))
              }
            />
          }
          label={t('users.active')}
        />
      )}
    </FormDialog>
  );
}
