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
  ConfirmModal,
  DateInput,
  Field,
  FormModal,
  RowActions,
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
      <RowActions
        name={milestone.name}
        main={
          track && !milestone.completedAt
            ? {
                label: t('plan.complete'),
                action: 'confirm',
                onClick: () => setDialog('complete'),
              }
            : reopenMilestones &&
              !!milestone.completedAt &&
              stage.status !== 'COMPLETED' && {
                label: t('plan.reopen'),
                action: 'revert',
                busy: reopen.busy,
                onClick: () =>
                  void reopen.run(`/milestones/${milestone.id}/reopen`, 'POST'),
              }
        }
        edit={edit && {onClick: () => setDialog('edit')}}
        more={[
          edit && {
            items: [
              {
                label: t('plan.remove'),
                action: 'danger',
                icon: 'ban',
                onClick: () => setDialog('delete'),
              },
            ],
          },
        ]}
      />
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
