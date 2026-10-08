import {useState} from 'react';
import {
  uploadReceipt,
  useExpenseAction,
  type Expense,
} from '@/entities/expense';
import {t} from '@/shared/i18n';
import {Field, FormModal} from '@/shared/ui';

const ACCEPT = 'application/pdf,image/jpeg,image/png,image/webp,image/heic';

/** Adds a receipt (invoice, ticket) to an expense: approval needs one. */
export function AttachReceiptModal({
  projectId,
  expense,
  onClose,
}: {
  projectId: number;
  expense: Expense;
  onClose: () => void;
}) {
  const action = useExpenseAction(projectId);
  const [file, setFile] = useState<File | null>(null);
  const [missing, setMissing] = useState(false);

  const onSubmit = async () => {
    if (!file) {
      setMissing(true);

      return;
    }
    if (await action.run(() => uploadReceipt(expense.id, file))) {
      onClose();
    }
  };

  return (
    <FormModal
      action="setup"
      title={t('expenses.attachTitle', {description: expense.description})}
      submitLabel={t('finance.attach')}
      submit={action}
      onClose={onClose}
      onSubmit={() => void onSubmit()}
    >
      <Field
        label={t('expenses.receipt')}
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
