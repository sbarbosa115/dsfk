import {Link, useParams} from 'react-router';
import {t} from '@/shared/i18n';
import {Alert, EmptyState, PageHeader} from '@/shared/ui';
import {topicById} from '../model/content';

/** One guide: its sections with steps, notes and screenshots, and the related guides. */
export function HelpTopicPage() {
  const {id = ''} = useParams();
  const topic = topicById(id);

  if (!topic) {
    return (
      <>
        <Link to="/help" className="btn btn-ghost btn-sm">
          ← {t('help.back')}
        </Link>
        <EmptyState>{t('help.notFound')}</EmptyState>
      </>
    );
  }

  return (
    <article className="help-topic">
      <Link to="/help" className="btn btn-ghost btn-sm">
        ← {t('help.back')}
      </Link>
      <PageHeader title={topic.title} subtitle={topic.summary} />
      {topic.sections.map((section, i) => (
        <section key={i} className="card">
          {section.heading && <h2>{section.heading}</h2>}
          {section.body?.map((line, j) => (
            <p key={j}>{line}</p>
          ))}
          {section.steps && (
            <ol className="help-steps">
              {section.steps.map((step, j) => (
                <li key={j}>{step}</li>
              ))}
            </ol>
          )}
          {section.note && <Alert kind="info">{section.note}</Alert>}
          {section.image && (
            <figure className="help-figure">
              <img
                src={section.image.src}
                alt={section.image.caption}
                loading="lazy"
              />
              <figcaption className="small muted">
                {section.image.caption}
              </figcaption>
            </figure>
          )}
        </section>
      ))}
      {topic.related && topic.related.length > 0 && (
        <section className="card">
          <h2>{t('help.related')}</h2>
          <ul>
            {topic.related.map((relatedId) => {
              const related = topicById(relatedId);

              return related ? (
                <li key={relatedId}>
                  <Link to={`/help/${relatedId}`}>{related.title}</Link>
                </li>
              ) : null;
            })}
          </ul>
        </section>
      )}
    </article>
  );
}
