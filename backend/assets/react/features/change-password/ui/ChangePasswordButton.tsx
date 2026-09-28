import {useState} from 'react';
import {changePassword} from '@/entities/session';
import {t} from '@/shared/i18n';
import {useForm, useSubmit} from '@/shared/lib/forms';
import {Alert, Field, FormModal} from '@/shared/ui';

/** In the sidebar footer: every user changes their own password. */
export function ChangePasswordButton() {
  const [open, setOpen] = useState(false);

  return (
    <>
      <button
        type="button"
        className="btn btn-ghost btn-sm btn-block"
        onClick={() => setOpen(true)}
      >
        {t('account.changePassword')}
      </button>
      {open && <ChangePasswordModal onClose={() => setOpen(false)} />}
    </>
  );
}

function ChangePasswordModal({onClose}: {onClose: () => void}) {
  const form = useForm({currentPassword: '', newPassword: ''});
  const submit = useSubmit();
  const [done, setDone] = useState(false);

  const onSubmit = async () => {
    const result = await submit.run(() => changePassword(form.values));
    if (result.ok) {
      setDone(true);
      form.setValues({currentPassword: '', newPassword: ''});
    }
  };

  return (
    <FormModal
      title={t('account.changePassword')}
      onClose={onClose}
      onSubmit={onSubmit}
      submit={submit}
    >
      <Alert kind="success">{done ? t('account.changed') : null}</Alert>
      <Field
        label={t('account.current')}
        error={submit.errors['currentPassword']}
      >
        <input
          type="password"
          autoComplete="current-password"
          autoFocus
          {...form.bind('currentPassword')}
        />
      </Field>
      <Field
        label={t('account.new')}
        hint={t('account.newHint')}
        error={submit.errors['newPassword']}
      >
        <input
          type="password"
          autoComplete="new-password"
          {...form.bind('newPassword')}
        />
      </Field>
    </FormModal>
  );
}
