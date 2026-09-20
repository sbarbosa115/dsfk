import type { CurrentUser } from '../../api/types'
import type { HelpAudience, HelpTopic } from './content'

export interface HelpSearchResult {
  topic: HelpTopic
  score: number
  /** Line of the topic that matched, shown under the title in the results list. */
  snippet: string
}

/** Lowercase and strip accents so "depósito" is found by typing "deposito". */
export function normalize(value: string): string {
  return value
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
}

export function terms(query: string): string[] {
  return normalize(query).split(/\s+/).filter(Boolean)
}

/** The audiences a user belongs to: admins are ADMIN, everyone else gets one entry per project role. */
export function audiencesOf(user: CurrentUser | null): HelpAudience[] {
  const audiences = new Set<HelpAudience>()
  if (user?.admin) {
    audiences.add('ADMIN')
  }
  // A super admin is an admin, plus the guides for what only they can do.
  if (user?.superAdmin) {
    audiences.add('SUPER_ADMIN')
  }
  for (const membership of user?.memberships ?? []) {
    audiences.add(membership.role)
  }

  return [...audiences]
}

export function visibleTo(topic: HelpTopic, audiences: HelpAudience[]): boolean {
  return topic.audiences.some((audience) => audiences.includes(audience))
}

export function topicsFor(topics: HelpTopic[], audiences: HelpAudience[]): HelpTopic[] {
  return topics.filter((topic) => visibleTo(topic, audiences))
}

/** Every searchable line of a topic, in descending order of weight. */
function haystack(topic: HelpTopic): { text: string; weight: number }[] {
  const fields = [
    { text: topic.title, weight: 10 },
    { text: topic.keywords.join(' '), weight: 6 },
    { text: topic.summary, weight: 4 },
  ]
  for (const section of topic.sections) {
    if (section.heading) {
      fields.push({ text: section.heading, weight: 3 })
    }
    for (const line of [...(section.body ?? []), ...(section.steps ?? []), section.note ?? '']) {
      if (line) {
        fields.push({ text: line, weight: 1 })
      }
    }
  }

  return fields
}

/**
 * Ranks topics that contain *every* search term. Searching is done over the topics the
 * caller already filtered by role, so a user never finds a topic they cannot open.
 */
export function searchTopics(topics: HelpTopic[], query: string): HelpSearchResult[] {
  const searched = terms(query)
  if (searched.length === 0) {
    return topics.map((topic) => ({ topic, score: 0, snippet: topic.summary }))
  }

  const results: HelpSearchResult[] = []
  for (const topic of topics) {
    const fields = haystack(topic).map((field) => ({ ...field, text: normalize(field.text) }))
    let score = 0
    let matchedAll = true

    for (const term of searched) {
      const hits = fields.filter((field) => field.text.includes(term))
      if (hits.length === 0) {
        matchedAll = false
        break
      }
      // A whole-word hit beats a hit in the middle of a longer word.
      score += Math.max(...hits.map((hit) => (new RegExp(`\\b${term}`).test(hit.text) ? hit.weight * 2 : hit.weight)))
    }

    if (matchedAll) {
      results.push({ topic, score, snippet: snippetFor(topic, searched[0]) })
    }
  }

  return results.sort((a, b) => b.score - a.score || a.topic.title.localeCompare(b.topic.title))
}

/** Prefers a body line containing the term, so the result explains why it matched. */
function snippetFor(topic: HelpTopic, term: string): string {
  const lines = topic.sections.flatMap((section) => [...(section.body ?? []), ...(section.steps ?? [])])
  const match = lines.find((line) => normalize(line).includes(term))

  return match ?? topic.summary
}
