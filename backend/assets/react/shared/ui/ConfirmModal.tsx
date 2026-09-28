import type {ReactNode} from 'react';
import {t} from '@/shared/i18n';
import {ActionButton, Alert, Button, Modal, type Action} from './ui';

/**
 * Asks before an action that is hard to undo (disable, void, reject). The confirming button keeps the colour of
 * its kind of action, so "Desactivar" is red here as in the row it came from.
 */
export function ConfirmModal({
  title,
  children,
  confirmLabel,
  action = 'danger',
  busy = false,
  error,
  onConfirm,
  onClose,
}: {
  title: string;
  children: ReactNode;
  confirmLabel: string;
  action?: Action;
  busy?: boolean;
  error?: string | null;
  onConfirm: () => void;
  onClose: () => void;
}) {
  return (
    <Modal title={title} onClose={onClose}>
      <Alert kind="error">{error}</Alert>
      <p>{children}</p>
      <div className="form-actions">
        <Button variant="ghost" onClick={onClose}>
          {t('common.cancel')}
        </Button>
        <ActionButton action={action} size="md" busy={busy} onClick={onConfirm}>
          {confirmLabel}
        </ActionButton>
      </div>
    </Modal>
  );
}
