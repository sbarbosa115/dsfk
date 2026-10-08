import {useState} from 'react';
import {usePlanAction, type Plan} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {ActionButton, ConfirmModal, Field, FormModal} from '@/shared/ui';

/**
 * The budget's workflow buttons: the PM (or Admin) sends a complete draft for approval; the Admin approves it
 * or returns it with what to change.
 */
export function BudgetActions({plan}: {plan: Plan}) {
  const [dialog, setDialog] = useState<'submit' | 'approve' | 'return' | null>(
    null,
  );
  const action = usePlanAction();
  const [comment, setComment] = useState('');
  const base = `/projects/${plan.project.id}/budget`;
  const close = () => setDialog(null);
  const incomplete = (plan.issues?.length ?? 0) > 0;

  const go = async (path: string, body?: unknown) => {
    if (await action.run(`${base}/${path}`, 'POST', body)) {
      close();
    }
  };

  return (
    <>
      {/* A draft is sent, a sent budget is reviewed: never both, so the card has one main action. */}
      {plan.permissions.submit ? (
        <ActionButton
          action="confirm"
          main
          size="md"
          icon="check"
          disabled={incomplete}
          onClick={() => setDialog('submit')}
        >
          {t('plan.submit')}
        </ActionButton>
      ) : plan.permissions.review ? (
        <>
          <ActionButton
            action="revert"
            size="md"
            onClick={() => setDialog('return')}
          >
            {t('plan.returnBudget')}
          </ActionButton>
          <ActionButton
            action="confirm"
            main
            size="md"
            icon="check"
            onClick={() => setDialog('approve')}
          >
            {t('plan.approve')}
          </ActionButton>
        </>
      ) : null}
      {dialog === 'submit' && (
        <ConfirmModal
          title={t('plan.submit')}
          confirmLabel={t('plan.submit')}
          action="confirm"
          busy={action.busy}
          error={action.formError}
          onConfirm={() => void go('submit')}
          onClose={close}
        >
          {t('plan.submitConfirm')}
        </ConfirmModal>
      )}
      {dialog === 'approve' && (
        <ConfirmModal
          title={t('plan.approve')}
          confirmLabel={t('plan.approve')}
          action="confirm"
          busy={action.busy}
          error={action.formError}
          onConfirm={() => void go('approve')}
          onClose={close}
        >
          {t('plan.approveConfirm')}
        </ConfirmModal>
      )}
      {dialog === 'return' && (
        <FormModal
          action="revert"
          title={t('plan.returnBudget')}
          submitLabel={t('plan.returnBudget')}
          submit={action}
          onSubmit={() => void go('return', {comment})}
          onClose={close}
        >
          <Field
            label={t('plan.returnComment')}
            error={action.errors['comment']}
            className="span-2"
          >
            <textarea
              rows={4}
              autoFocus
              value={comment}
              onChange={(e) => setComment(e.target.value)}
            />
          </Field>
        </FormModal>
      )}
    </>
  );
}
