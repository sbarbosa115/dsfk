import {Link} from 'react-router';
import {t} from '@/shared/i18n';
import {EmptyState, PageHeader} from '@/shared/ui';

export function NotFoundPage() {
  return (
    <>
      <PageHeader title={t('common.notFound')} />
      <EmptyState
        action={
          <Link className="btn btn-primary" to="/">
            {t('common.home')}
          </Link>
        }
      />
    </>
  );
}
