import {useMemo, useState} from 'react';
import {Link} from 'react-router';
import {useSession} from '@/entities/session';
import {t} from '@/shared/i18n';
import {Checkbox, EmptyState, PageHeader, SearchInput} from '@/shared/ui';
import {helpTopics} from '../model/content';
import {audiencesOf, searchTopics, topicsFor} from '../model/search';

/** The manual: the guides for the person's roles, searchable. Admins may open every guide. */
export function HelpPage() {
  const {user} = useSession();
  const [query, setQuery] = useState('');
  const [everything, setEverything] = useState(false);
  const available = useMemo(
    () =>
      everything && user?.admin
        ? helpTopics
        : topicsFor(helpTopics, audiencesOf(user)),
    [everything, user],
  );
  const results = useMemo(
    () => searchTopics(available, query),
    [available, query],
  );

  return (
    <>
      <PageHeader title={t('help.title')} subtitle={t('help.subtitle')} />
      <div className="toolbar">
        <label className="filter-select filter-search help-search">
          <span className="filter-select-label">{t('help.search')}</span>
          <SearchInput
            value={query}
            onChange={setQuery}
            placeholder={t('help.searchHint')}
          />
        </label>
        {user?.admin && (
          <Checkbox
            label={t('help.showAll')}
            checked={everything}
            onChange={setEverything}
          />
        )}
      </div>
      {results.length === 0 ? (
        <EmptyState>{t('help.noResults')}</EmptyState>
      ) : (
        <ul className="help-list">
          {results.map(({topic, snippet}) => (
            <li key={topic.id}>
              <Link to={`/help/${topic.id}`} className="card help-card">
                <h2>{topic.title}</h2>
                <p className="muted small">
                  {query.trim() ? snippet : topic.summary}
                </p>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
