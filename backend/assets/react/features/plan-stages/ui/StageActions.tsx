import {useState} from 'react';
import {usePlanAction, type Plan, type PlanStage} from '@/entities/plan';
import {t} from '@/shared/i18n';
import {today} from '@/shared/lib/format';
import {
  ActionButton,
  Alert,
  ConfirmModal,
  DateInput,
  Field,
  FormModal,
  IconButton,
} from '@/shared/ui';
import {StageFormModal} from './StageFormModal';

/**
 * What can be done to a stage, in the house order: start it (after approval), then edit, move, and delete
 * last (only while the budget is a draft).
 */
export function StageActions({
  plan,
  stage,
  index,
}: {
  plan: Plan;
  stage: PlanStage;
  index: number;
}) {
  const [dialog, setDialog] = useState<'edit' | 'delete' | 'start' | null>(
    null,
  );
  const move = usePlanAction();
  const close = () => setDialog(null);
  const {edit, track} = plan.permissions;

  const moveTo = (to: number) => {
    const ids = plan.stages.map((s) => s.id);
    const [id] = ids.splice(index, 1);
    ids.splice(to, 0, id!);
    void move.run(`/projects/${plan.project.id}/stages/order`, 'PUT', {ids});
  };

  if (!edit && !track) {
    return null;
  }

  return (
    <div className="row-actions">
      {track && stage.status === 'PENDING' && (
        <ActionButton action="setup" onClick={() => setDialog('start')}>
          {t('plan.startStage')}
        </ActionButton>
      )}
      <IconButton
        icon="pencil"
        label={t('plan.editStage')}
        onClick={() => setDialog('edit')}
      />
      {edit && (
        <>
          <IconButton
            icon="arrowUp"
            action="open"
            label={t('plan.moveUp')}
            disabled={index === 0}
            onClick={() => moveTo(index - 1)}
          />
          <IconButton
            icon="arrowDown"
            action="open"
            label={t('plan.moveDown')}
            disabled={index === plan.stages.length - 1}
            onClick={() => moveTo(index + 1)}
          />
          <ActionButton action="danger" onClick={() => setDialog('delete')}>
            {t('plan.deleteStage')}
          </ActionButton>
        </>
      )}
      {move.formError && <Alert kind="error">{move.formError}</Alert>}
      {dialog === 'edit' && (
        <StageFormModal plan={plan} stage={stage} onClose={close} />
      )}
      {dialog === 'delete' && <DeleteStage stage={stage} onClose={close} />}
      {dialog === 'start' && <StartStage stage={stage} onClose={close} />}
    </div>
  );
}

function DeleteStage({
  stage,
  onClose,
}: {
  stage: PlanStage;
  onClose: () => void;
}) {
  const action = usePlanAction();

  return (
    <ConfirmModal
      title={t('plan.deleteStage')}
      confirmLabel={t('plan.deleteStage')}
      busy={action.busy}
      error={action.formError}
      onConfirm={async () => {
        if (await action.run(`/stages/${stage.id}`, 'DELETE')) {
          onClose();
        }
      }}
      onClose={onClose}
    >
      {t('plan.deleteStageConfirm', {name: stage.name})}
    </ConfirmModal>
  );
}

function StartStage({stage, onClose}: {stage: PlanStage; onClose: () => void}) {
  const action = usePlanAction();
  const [date, setDate] = useState(today());

  return (
    <FormModal
      title={`${t('plan.startStage')} · ${stage.name}`}
      submitLabel={t('plan.startStage')}
      submit={action}
      onClose={onClose}
      onSubmit={async () => {
        if (
          await action.run(`/stages/${stage.id}/start`, 'POST', {
            actualStart: date,
          })
        ) {
          onClose();
        }
      }}
    >
      <Field label={t('plan.actualStart')} error={action.errors['actualStart']}>
        <DateInput
          max={today()}
          value={date}
          onChange={(e) => setDate(e.target.value)}
        />
      </Field>
    </FormModal>
  );
}
