import {useState} from 'react';
import {
  usePlanAction,
  type Plan,
  type PlanMilestone,
  type PlanStage,
} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {today} from '@/shared/lib/format';
import {
  ActionButton,
  ConfirmModal,
  DateInput,
  Field,
  FormModal,
  IconButton,
} from '@/shared/ui';
import {MilestoneFormModal} from './MilestoneFormModal';

/**
 * A milestone's row actions in the house order: mark it met (after approval) or reopen it (Admin), then edit,
 * and remove last (while the budget is a draft).
 */
export function MilestoneActions({
  plan,
  stage,
  milestone,
}: {
  plan: Plan;
  stage: PlanStage;
  milestone: PlanMilestone;
}) {
  const [dialog, setDialog] = useState<'complete' | 'edit' | 'delete' | null>(
    null,
  );
  const reopen = usePlanAction();
  const close = () => setDialog(null);
  const {edit, track, reopenMilestones} = plan.permissions;

  return (
    <>
      {track && !milestone.completedAt && (
        <ActionButton action="confirm" onClick={() => setDialog('complete')}>
          {t('plan.complete')}
        </ActionButton>
      )}
      {reopenMilestones &&
        milestone.completedAt &&
        stage.status !== 'COMPLETED' && (
          <ActionButton
            action="revert"
            busy={reopen.busy}
            onClick={() =>
              void reopen.run(`/milestones/${milestone.id}/reopen`, 'POST')
            }
          >
            {t('plan.reopen')}
          </ActionButton>
        )}
      {edit && (
        <>
          <IconButton
            icon="pencil"
            label={t('plan.editMilestone')}
            onClick={() => setDialog('edit')}
          />
          <ActionButton action="danger" onClick={() => setDialog('delete')}>
            {t('plan.remove')}
          </ActionButton>
        </>
      )}
      {dialog === 'complete' && (
        <CompleteModal milestone={milestone} onClose={close} />
      )}
      {dialog === 'edit' && (
        <MilestoneFormModal
          stage={stage}
          milestone={milestone}
          onClose={close}
        />
      )}
      {dialog === 'delete' && (
        <DeleteModal milestone={milestone} onClose={close} />
      )}
    </>
  );
}

function CompleteModal({
  milestone,
  onClose,
}: {
  milestone: PlanMilestone;
  onClose: () => void;
}) {
  const action = usePlanAction();
  const [date, setDate] = useState(today());
  const [notes, setNotes] = useState('');

  return (
    <FormModal
      title={`${t('plan.completeTitle')} · ${milestone.name}`}
      submitLabel={t('plan.complete')}
      submit={action}
      onClose={onClose}
      onSubmit={async () => {
        if (
          await action.run(`/milestones/${milestone.id}/complete`, 'POST', {
            completedAt: date,
            notes: notes || null,
          })
        ) {
          onClose();
        }
      }}
    >
      <Field label={t('plan.completedAt')} error={action.errors['completedAt']}>
        <DateInput
          max={today()}
          value={date}
          onChange={(e) => setDate(e.target.value)}
        />
      </Field>
      <Field
        label={t('plan.notes')}
        optional
        error={action.errors['notes']}
        className="span-2"
      >
        <textarea
          rows={3}
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
        />
      </Field>
    </FormModal>
  );
}

function DeleteModal({
  milestone,
  onClose,
}: {
  milestone: PlanMilestone;
  onClose: () => void;
}) {
  const action = usePlanAction();

  return (
    <ConfirmModal
      title={t('plan.deleteMilestone')}
      confirmLabel={t('plan.remove')}
      busy={action.busy}
      error={action.formError}
      onConfirm={async () => {
        if (await action.run(`/milestones/${milestone.id}`, 'DELETE')) {
          onClose();
        }
      }}
      onClose={onClose}
    >
      {t('plan.deleteMilestoneConfirm', {name: milestone.name})}
    </ConfirmModal>
  );
}
