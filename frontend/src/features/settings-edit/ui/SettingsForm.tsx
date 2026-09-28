import {Alert, Button, Paper, Stack, TextField} from '@mui/material';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useState, type FormEvent} from 'react';
import {useTranslation} from 'react-i18next';
import {saveSettings, SETTINGS_KEY, type Settings} from '@/entities/settings';
import {errorMessage, fieldError} from '@/shared/lib/errors';
import {parsePercents} from '../model/percents';

export function SettingsForm({initial}: {initial: Settings}) {
  const {t} = useTranslation();
  const queryClient = useQueryClient();
  const [form, setForm] = useState(() => toForm(initial));

  const save = useMutation({
    mutationFn: () =>
      saveSettings({
        defaultCurrency: form.defaultCurrency.toUpperCase(),
        teamLeadExpenseLimit: form.teamLeadExpenseLimit,
        pettyCashLowBalancePercent: Number(form.pettyCashLowBalancePercent),
        budgetWarningPercents: parsePercents(form.budgetWarningPercents),
      }),
    onSuccess: (saved) => {
      queryClient.setQueryData(SETTINGS_KEY, saved);
      // Show what was stored (thresholds sorted and without repeats).
      setForm(toForm(saved));
    },
  });

  const field = (name: keyof typeof form) => ({
    value: form[name],
    onChange: (e: {target: {value: string}}) =>
      setForm((f) => ({...f, [name]: e.target.value})),
    error: !!fieldError(save.error, name),
    helperText: fieldError(save.error, name) ?? t(`settings.${name}Hint`),
    label: t(`settings.${name}`),
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    save.mutate();
  };

  return (
    <Paper sx={{p: 3, maxWidth: 560}}>
      <Stack component="form" spacing={2.5} onSubmit={handleSubmit} noValidate>
        {save.isSuccess && (
          <Alert severity="success">{t('common.saved')}</Alert>
        )}
        {!!save.error && (
          <Alert severity="error">{errorMessage(t, save.error)}</Alert>
        )}
        <TextField
          {...field('defaultCurrency')}
          slotProps={{
            htmlInput: {maxLength: 3, style: {textTransform: 'uppercase'}},
          }}
        />
        <TextField
          {...field('teamLeadExpenseLimit')}
          slotProps={{htmlInput: {inputMode: 'decimal'}}}
        />
        <TextField {...field('pettyCashLowBalancePercent')} type="number" />
        <TextField {...field('budgetWarningPercents')} />
        <Stack direction="row" sx={{justifyContent: 'flex-end'}}>
          <Button type="submit" variant="contained" disabled={save.isPending}>
            {t('common.save')}
          </Button>
        </Stack>
      </Stack>
    </Paper>
  );
}

function toForm(settings: Settings) {
  return {
    defaultCurrency: settings.defaultCurrency,
    teamLeadExpenseLimit: settings.teamLeadExpenseLimit,
    pettyCashLowBalancePercent: String(settings.pettyCashLowBalancePercent),
    budgetWarningPercents: settings.budgetWarningPercents.join(', '),
  };
}
