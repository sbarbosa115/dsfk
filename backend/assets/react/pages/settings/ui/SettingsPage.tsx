import {useQuery} from '@tanstack/react-query';
import {fetchSettings, SETTINGS_KEY} from '@/entities/settings';
import {SettingsForm} from '@/features/settings-edit';
import {t} from '@/shared/i18n';
import {ErrorState, Loading, PageHeader} from '@/shared/ui';

export function SettingsPage() {
  const settings = useQuery({queryKey: SETTINGS_KEY, queryFn: fetchSettings});

  return (
    <>
      <PageHeader
        title={t('settings.title')}
        subtitle={t('settings.subtitle')}
      />
      {settings.error ? (
        <ErrorState
          error={settings.error}
          onRetry={() => void settings.refetch()}
        />
      ) : settings.data ? (
        <SettingsForm initial={settings.data} />
      ) : (
        <Loading />
      )}
    </>
  );
}
