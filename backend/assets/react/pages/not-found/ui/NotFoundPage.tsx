import {Link} from 'react-router';
import {t} from '@/shared/i18n';
import {actionClass, EmptyState, PageHeader} from '@/shared/ui';

export function NotFoundPage() {
  return (
    <>
      <PageHeader title={t('common.notFound')} />
      <EmptyState
        action={
          <Link className={actionClass('open', 'is-main', 'md')} to="/">
            {t('common.home')}
          </Link>
        }
      >
        {t('common.notFoundHelp')}
      </EmptyState>
    </>
  );
}
