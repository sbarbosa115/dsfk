import {useQueryClient} from '@tanstack/react-query';
import {useState} from 'react';
import {SWITCHABLE_USERS_KEY} from '@/entities/session';
import {updateUser, USERS_KEY, type User} from '@/entities/user';
import {t} from '@/shared/i18n';
import {errorMessage} from '@/shared/lib/errors';
import {ConfirmModal, IconButton} from '@/shared/ui';

/**
 * The on/off icon of a user's row: disabling asks first (the person is signed out and cannot come back);
 * enabling does not. `onDone` gets the message to show above the table.
 */
export function ToggleActiveButton({
  user,
  disabled,
  onDone,
}: {
  user: User;
  disabled?: boolean;
  onDone: (message: string) => void;
}) {
  const queryClient = useQueryClient();
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const toggle = async () => {
    setBusy(true);
    setError(null);
    try {
      await updateUser(user.id, {active: !user.active});
      void queryClient.invalidateQueries({queryKey: USERS_KEY});
      void queryClient.invalidateQueries({queryKey: SWITCHABLE_USERS_KEY});
      setConfirming(false);
      onDone(
        t(user.active ? 'users.disabled' : 'users.enabled', {
          name: user.fullName,
        }),
      );
    } catch (e) {
      setError(errorMessage(e));
      if (!user.active) {
        onDone(errorMessage(e));
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <IconButton
        icon={user.active ? 'ban' : 'check'}
        label={user.active ? t('common.disable') : t('common.enable')}
        busy={busy && !confirming}
        disabled={disabled}
        onClick={() => (user.active ? setConfirming(true) : void toggle())}
      />
      {confirming && (
        <ConfirmModal
          title={t('common.disable')}
          confirmLabel={t('common.disable')}
          busy={busy}
          error={error}
          onConfirm={() => void toggle()}
          onClose={() => setConfirming(false)}
        >
          {t('users.confirmDisable', {name: user.fullName})}
        </ConfirmModal>
      )}
    </>
  );
}
