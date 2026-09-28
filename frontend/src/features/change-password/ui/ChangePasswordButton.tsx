import KeyIcon from '@mui/icons-material/KeyOutlined';
import {Alert, IconButton, TextField} from '@mui/material';
import {useMutation} from '@tanstack/react-query';
import {useState} from 'react';
import {useTranslation} from 'react-i18next';
import {changePassword} from '@/entities/session';
import {fieldError} from '@/shared/lib/errors';
import {FormDialog} from '@/shared/ui/FormDialog';

/** Top-bar button: every user changes their own password. */
export function ChangePasswordButton() {
  const {t} = useTranslation();
  const [open, setOpen] = useState(false);

  return (
    <>
      <IconButton
        color="inherit"
        onClick={() => setOpen(true)}
        title={t('account.changePassword')}
        aria-label={t('account.changePassword')}
      >
        <KeyIcon />
      </IconButton>
      {open && <ChangePasswordDialog onClose={() => setOpen(false)} />}
    </>
  );
}

function ChangePasswordDialog({onClose}: {onClose: () => void}) {
  const {t} = useTranslation();
  const [form, setForm] = useState({currentPassword: '', newPassword: ''});
  const save = useMutation({mutationFn: () => changePassword(form)});
  const field = (name: keyof typeof form) => ({
    value: form[name],
    type: 'password',
    onChange: (e: {target: {value: string}}) =>
      setForm({...form, [name]: e.target.value}),
    error: !!fieldError(save.error, name),
    helperText: fieldError(save.error, name),
  });

  return (
    <FormDialog
      title={t('account.changePassword')}
      error={save.error}
      pending={save.isPending}
      disabled={
        save.isSuccess || !form.currentPassword || form.newPassword.length < 8
      }
      onSubmit={() => save.mutate()}
      onClose={onClose}
      maxWidth="xs"
    >
      {save.isSuccess && (
        <Alert severity="success">{t('account.changed')}</Alert>
      )}
      <TextField
        label={t('account.current')}
        autoComplete="current-password"
        autoFocus
        {...field('currentPassword')}
      />
      <TextField
        label={t('account.new')}
        autoComplete="new-password"
        {...field('newPassword')}
      />
    </FormDialog>
  );
}
