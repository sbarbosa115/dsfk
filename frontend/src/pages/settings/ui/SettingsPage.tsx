import {useQuery} from '@tanstack/react-query';
import {useTranslation} from 'react-i18next';
import {fetchSettings, SETTINGS_KEY} from '@/entities/settings';
import {SettingsForm} from '@/features/settings-edit';
import {PageHeader} from '@/shared/ui/PageHeader';
import {QueryState} from '@/shared/ui/QueryState';

export function SettingsPage() {
  const {t} = useTranslation();
  const settings = useQuery({queryKey: SETTINGS_KEY, queryFn: fetchSettings});

  return (
    <>
      <PageHeader
        title={t('settings.title')}
        subtitle={t('settings.subtitle')}
      />
      <QueryState query={settings}>
        {(data) => <SettingsForm initial={data} />}
      </QueryState>
    </>
  );
}
