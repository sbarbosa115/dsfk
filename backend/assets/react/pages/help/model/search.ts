import type {CurrentUser} from '@/entities/session';
import type {HelpAudience, HelpTopic} from './content';

export interface HelpSearchResult {
  topic: HelpTopic;
  score: number;
  /** The topic's line that matched, shown under its title in the results. */
  snippet: string;
}

/** Lowercase and without accents, so "depósito" is found by typing "deposito". */
export function normalize(value: string): string {
  return value
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '');
}

export function terms(query: string): string[] {
  return normalize(query).split(/\s+/).filter(Boolean);
}

/** The audiences a person belongs to: admins are ADMIN (and SUPER_ADMIN), everyone else one per project role. */
export function audiencesOf(
  user: CurrentUser | null | undefined,
): HelpAudience[] {
  const audiences = new Set<HelpAudience>();
  if (user?.admin) {
    audiences.add('ADMIN');
  }
  if (user?.superAdmin) {
    audiences.add('SUPER_ADMIN');
  }
  for (const membership of user?.memberships ?? []) {
    audiences.add(membership.role as HelpAudience);
  }

  return [...audiences];
}

export function topicsFor(
  topics: HelpTopic[],
  audiences: HelpAudience[],
): HelpTopic[] {
  return topics.filter((topic) =>
    topic.audiences.some((a) => audiences.includes(a)),
  );
}

/** Every searchable line of a topic, heaviest first. */
function haystack(topic: HelpTopic): Array<{text: string; weight: number}> {
  const fields = [
    {text: topic.title, weight: 10},
    {text: topic.keywords.join(' '), weight: 6},
    {text: topic.summary, weight: 4},
  ];
  for (const section of topic.sections) {
    if (section.heading) {
      fields.push({text: section.heading, weight: 3});
    }
    for (const line of [
      ...(section.body ?? []),
      ...(section.steps ?? []),
      section.note ?? '',
    ]) {
      if (line) {
        fields.push({text: line, weight: 1});
      }
    }
  }

  return fields;
}

/**
 * Ranks the topics that contain every search term. It searches only the topics the caller already filtered by
 * role, so nobody finds a guide they cannot open.
 */
export function searchTopics(
  topics: HelpTopic[],
  query: string,
): HelpSearchResult[] {
  const searched = terms(query);
  if (searched.length === 0) {
    return topics.map((topic) => ({topic, score: 0, snippet: topic.summary}));
  }

  const results: HelpSearchResult[] = [];
  for (const topic of topics) {
    const fields = haystack(topic).map((f) => ({
      ...f,
      text: normalize(f.text),
    }));
    let score = 0;
    let matchedAll = true;
    for (const term of searched) {
      const hits = fields.filter((f) => f.text.includes(term));
      if (hits.length === 0) {
        matchedAll = false;
        break;
      }
      // A whole-word hit beats one in the middle of a longer word.
      const word = new RegExp(
        `\\b${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`,
      );
      score += Math.max(
        ...hits.map((h) => (word.test(h.text) ? h.weight * 2 : h.weight)),
      );
    }
    if (matchedAll) {
      results.push({topic, score, snippet: snippetFor(topic, searched[0]!)});
    }
  }

  return results.sort(
    (a, b) => b.score - a.score || a.topic.title.localeCompare(b.topic.title),
  );
}

/** A body line with the term, so the result says why it matched. */
function snippetFor(topic: HelpTopic, term: string): string {
  const lines = topic.sections.flatMap((s) => [
    ...(s.body ?? []),
    ...(s.steps ?? []),
  ]);

  return lines.find((line) => normalize(line).includes(term)) ?? topic.summary;
}
