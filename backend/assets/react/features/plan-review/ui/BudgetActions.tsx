import {useState} from 'react';
import {usePlanAction, type Plan} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {ActionButton, ConfirmModal, Field, FormModal} from '@/shared/ui';

/**
 * The budget's workflow buttons: the PM (or Admin) sends a complete draft for approval; the Admin approves it
 * or returns it with what to change. Once the budget is approved the same buttons carry its open addition
 * (Adición), worded for it.
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
  const amendment = plan.budgetStatus === 'APPROVED';
  const label = {
    submit: amendment ? t('plan.amendment.submit') : t('plan.submit'),
    approve: amendment ? t('plan.amendment.approve') : t('plan.approve'),
    returnIt: amendment ? t('plan.amendment.returnIt') : t('plan.returnBudget'),
  };

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
          {label.submit}
        </ActionButton>
      ) : plan.permissions.review ? (
        <>
          <ActionButton
            action="revert"
            size="md"
            onClick={() => setDialog('return')}
          >
            {label.returnIt}
          </ActionButton>
          <ActionButton
            action="confirm"
            main
            size="md"
            icon="check"
            onClick={() => setDialog('approve')}
          >
            {label.approve}
          </ActionButton>
        </>
      ) : null}
      {dialog === 'submit' && (
        <ConfirmModal
          title={label.submit}
          confirmLabel={label.submit}
          action="confirm"
          busy={action.busy}
          error={action.formError}
          onConfirm={() => void go('submit')}
          onClose={close}
        >
          {amendment
            ? t('plan.amendment.submitConfirm')
            : t('plan.submitConfirm')}
        </ConfirmModal>
      )}
      {dialog === 'approve' && (
        <ConfirmModal
          title={label.approve}
          confirmLabel={label.approve}
          action="confirm"
          busy={action.busy}
          error={action.formError}
          onConfirm={() => void go('approve')}
          onClose={close}
        >
          {amendment
            ? t('plan.amendment.approveConfirm')
            : t('plan.approveConfirm')}
        </ConfirmModal>
      )}
      {dialog === 'return' && (
        <FormModal
          action="revert"
          title={label.returnIt}
          submitLabel={label.returnIt}
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
