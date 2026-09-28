import {useState} from 'react';
import {uploadProof, useFinanceAction, type Movement} from '@/entities/finance';
import {t} from '@/shared/i18n';
import {Field, FormModal} from '@/shared/ui';

const ACCEPT = 'application/pdf,image/jpeg,image/png,image/webp,image/heic';

/** Adds a proof (bank slip, transfer receipt) to a deposit. */
export function AttachProofModal({
  projectId,
  movement,
  onClose,
}: {
  projectId: number;
  movement: Movement;
  onClose: () => void;
}) {
  const action = useFinanceAction(projectId);
  const [file, setFile] = useState<File | null>(null);
  const [missing, setMissing] = useState(false);

  const onSubmit = async () => {
    if (!file) {
      setMissing(true);

      return;
    }
    if (await action.run(() => uploadProof(movement.id, file))) {
      onClose();
    }
  };

  return (
    <FormModal
      title={t('finance.attachTitle')}
      submitLabel={t('finance.attach')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field
        label={t('finance.proofFile')}
        hint={t('finance.proofHint')}
        className="span-2"
        error={
          action.errors['file'] ?? (missing ? t('finance.chooseFile') : null)
        }
      >
        <input
          type="file"
          accept={ACCEPT}
          onChange={(e) => {
            setFile(e.target.files?.[0] ?? null);
            setMissing(false);
            action.reset();
          }}
        />
      </Field>
    </FormModal>
  );
}
