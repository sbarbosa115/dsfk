import {useQueryClient} from '@tanstack/react-query';
import {SWITCHABLE_USERS_KEY, useSession} from '@/entities/session';
import {createUser, updateUser, USERS_KEY, type User} from '@/entities/user';
import {t} from '@/shared/i18n';
import {useForm, useSubmit} from '@/shared/lib/forms';
import {Checkbox, Field, FieldGroup, FormModal} from '@/shared/ui';

/** Creates a user (user = null) or edits one. Admins cannot take away their own access. */
export function UserDialog({
  user,
  onClose,
}: {
  user: User | null;
  onClose: () => void;
}) {
  const {user: me} = useSession();
  const queryClient = useQueryClient();
  const isSelf = user !== null && user.id === me?.id;
  const form = useForm({
    fullName: user?.fullName ?? '',
    email: user?.email ?? '',
    password: '',
    admin: user?.admin ?? false,
    superAdmin: user?.superAdmin ?? false,
  });
  const submit = useSubmit();

  const onSubmit = async () => {
    const {password, ...rest} = form.values;
    const result = await submit.run(() =>
      user
        ? updateUser(user.id, {...rest, password: password || null})
        : createUser({...rest, password}),
    );
    if (result.ok) {
      void queryClient.invalidateQueries({queryKey: USERS_KEY});
      // "Ver como" keeps its own copy of the users.
      void queryClient.invalidateQueries({queryKey: SWITCHABLE_USERS_KEY});
      onClose();
    }
  };

  return (
    <FormModal
      action={user ? 'confirm' : 'setup'}
      title={user ? t('users.edit') : t('users.new')}
      submitLabel={user ? t('common.save') : t('common.create')}
      onClose={onClose}
      onSubmit={onSubmit}
      submit={submit}
    >
      <Field label={t('users.fullName')} error={submit.errors['fullName']}>
        <input autoFocus {...form.bind('fullName')} />
      </Field>
      <Field label={t('users.email')} error={submit.errors['email']}>
        <input type="email" autoComplete="off" {...form.bind('email')} />
      </Field>
      <Field
        label={user ? t('users.newPassword') : t('users.password')}
        hint={user ? t('users.newPasswordHint') : t('users.passwordHint')}
        optional={!!user}
        error={submit.errors['password']}
      >
        <input
          type="password"
          autoComplete="new-password"
          {...form.bind('password')}
        />
      </Field>
      {!isSelf && (
        <FieldGroup label={t('users.access')} hint={t('users.adminHint')}>
          <Checkbox
            label={t('users.admin')}
            checked={form.values.admin}
            onChange={(checked) => {
              form.set('admin', checked);
              if (!checked) {
                form.set('superAdmin', false);
              }
            }}
          />
          {/* Granting "Ver como" is itself a super admin power, so ordinary admins never see it. */}
          {me?.superAdmin && (
            <Checkbox
              label={`${t('users.superAdmin')}: ${t('users.superAdminHint')}`}
              checked={form.values.superAdmin}
              onChange={(checked) => {
                form.set('superAdmin', checked);
                if (checked) {
                  form.set('admin', true);
                }
              }}
            />
          )}
        </FieldGroup>
      )}
    </FormModal>
  );
}
