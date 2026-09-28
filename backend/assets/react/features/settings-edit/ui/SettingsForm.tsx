import {useQueryClient} from '@tanstack/react-query';
import {useState} from 'react';
import {saveSettings, SETTINGS_KEY, type Settings} from '@/entities/settings';
import {t} from '@/shared/i18n';
import {formatAmountForInput, parseAmountInput} from '@/shared/lib/format';
import {useForm, useSubmit} from '@/shared/lib/forms';
import {Alert, Button, Field, MoneyField} from '@/shared/ui';
import {parsePercents} from '../model/percents';

function toForm(settings: Settings) {
  return {
    defaultCurrency: settings.defaultCurrency,
    teamLeadExpenseLimit: formatAmountForInput(settings.teamLeadExpenseLimit),
    pettyCashLowBalancePercent: String(settings.pettyCashLowBalancePercent),
    budgetWarningPercents: settings.budgetWarningPercents.join(', '),
  };
}

export function SettingsForm({initial}: {initial: Settings}) {
  const queryClient = useQueryClient();
  const form = useForm(toForm(initial));
  const submit = useSubmit();
  const [saved, setSaved] = useState(false);

  const onSubmit = async () => {
    setSaved(false);
    const {values} = form;
    const result = await submit.run(() =>
      saveSettings({
        defaultCurrency: values.defaultCurrency.toUpperCase(),
        // An amount that is not one goes as typed, so the API marks the field.
        teamLeadExpenseLimit:
          parseAmountInput(values.teamLeadExpenseLimit) ??
          values.teamLeadExpenseLimit,
        pettyCashLowBalancePercent: Number(values.pettyCashLowBalancePercent),
        budgetWarningPercents: parsePercents(values.budgetWarningPercents),
      }),
    );
    if (result.ok) {
      queryClient.setQueryData(SETTINGS_KEY, result.value);
      // Show what was stored (thresholds sorted and without repeats).
      form.setValues(toForm(result.value));
      setSaved(true);
    }
  };

  return (
    <section className="card settings-section">
      <form
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          void onSubmit();
        }}
      >
        <Alert kind="success">{saved ? t('common.saved') : null}</Alert>
        <Alert kind="error">{submit.formError}</Alert>
        <Field
          label={t('settings.defaultCurrency')}
          hint={t('settings.defaultCurrencyHint')}
          error={submit.errors['defaultCurrency']}
        >
          <input
            maxLength={3}
            className="uppercase settings-number"
            {...form.bind('defaultCurrency')}
          />
        </Field>
        <MoneyField
          label={t('settings.teamLeadExpenseLimit')}
          currency={form.values.defaultCurrency.toUpperCase()}
          hint={t('settings.teamLeadExpenseLimitHint')}
          error={submit.errors['teamLeadExpenseLimit']}
          value={form.values.teamLeadExpenseLimit}
          onChange={(text) => form.set('teamLeadExpenseLimit', text)}
        />
        <Field
          label={t('settings.pettyCashLowBalancePercent')}
          hint={t('settings.pettyCashLowBalancePercentHint')}
          error={submit.errors['pettyCashLowBalancePercent']}
        >
          <input
            type="number"
            min={1}
            max={100}
            className="settings-number"
            {...form.bind('pettyCashLowBalancePercent')}
          />
        </Field>
        <Field
          label={t('settings.budgetWarningPercents')}
          hint={t('settings.budgetWarningPercentsHint')}
          error={submit.errors['budgetWarningPercents']}
        >
          <input {...form.bind('budgetWarningPercents')} />
        </Field>
        <div className="form-actions">
          <Button type="submit" busy={submit.busy}>
            {t('common.save')}
          </Button>
        </div>
      </form>
    </section>
  );
}
