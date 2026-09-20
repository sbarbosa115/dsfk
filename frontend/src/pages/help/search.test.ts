import { describe, expect, it } from 'vitest'
import type { CurrentUser } from '../../api/types'
import { helpTopics, topicById } from './content'
import { audiencesOf, normalize, searchTopics, topicsFor } from './search'

const teamLead: CurrentUser = {
  id: 3, email: 'lider@x.co', fullName: 'Carlos Ruiz', admin: false, superAdmin: false,
  memberships: [{ projectId: 1, projectName: 'Torre', role: 'TEAM_LEAD' }],
  impersonator: null, canImpersonate: false,
}
const manager: CurrentUser = { ...teamLead, id: 2, memberships: [{ projectId: 1, projectName: 'Torre', role: 'PROJECT_MANAGER' }] }
const admin: CurrentUser = { ...teamLead, id: 1, admin: true, memberships: [] }
const superAdmin: CurrentUser = { ...admin, id: 0, superAdmin: true }

describe('audiencesOf', () => {
  it('maps a user to the audiences they belong to', () => {
    expect(audiencesOf(admin)).toEqual(['ADMIN'])
    expect(audiencesOf(teamLead)).toEqual(['TEAM_LEAD'])
    expect(audiencesOf(null)).toEqual([])
  })

  it('gives a super admin both the admin and the super admin guides', () => {
    expect(audiencesOf(superAdmin)).toEqual(['ADMIN', 'SUPER_ADMIN'])
  })

  it('covers every role of someone who leads one project and manages another', () => {
    const both: CurrentUser = {
      ...teamLead,
      memberships: [
        { projectId: 1, projectName: 'Torre', role: 'TEAM_LEAD' },
        { projectId: 2, projectName: 'Casa', role: 'PROJECT_MANAGER' },
      ],
    }

    expect(audiencesOf(both).sort()).toEqual(['PROJECT_MANAGER', 'TEAM_LEAD'])
  })
})

describe('topicsFor', () => {
  it('hides topics that belong to another role', () => {
    const ids = topicsFor(helpTopics, audiencesOf(teamLead)).map((topic) => topic.id)

    expect(ids).toContain('registrar-gasto')
    expect(ids).not.toContain('configuracion')
    expect(ids).not.toContain('aprobar-presupuesto')
  })

  it('gives the admin the admin topics and the manager the budget ones', () => {
    expect(topicsFor(helpTopics, audiencesOf(admin)).map((t) => t.id)).toContain('auditoria')
    expect(topicsFor(helpTopics, audiencesOf(manager)).map((t) => t.id)).toContain('enviar-presupuesto')
  })

  it('shows nothing to a user without a role', () => {
    expect(topicsFor(helpTopics, audiencesOf(null))).toEqual([])
  })

  it('keeps "Ver como" for super admins only', () => {
    expect(topicsFor(helpTopics, audiencesOf(superAdmin)).map((t) => t.id)).toContain('ver-como')
    expect(topicsFor(helpTopics, audiencesOf(admin)).map((t) => t.id)).not.toContain('ver-como')
    expect(topicsFor(helpTopics, audiencesOf(teamLead)).map((t) => t.id)).not.toContain('ver-como')
  })
})

describe('searchTopics', () => {
  it('ignores accents and capitals', () => {
    expect(normalize('Depósito')).toBe('deposito')

    const found = searchTopics(helpTopics, 'deposito').map((r) => r.topic.id)

    expect(found).toContain('depositos')
  })

  it('requires every term to match', () => {
    const both = searchTopics(helpTopics, 'cerrar ciclo').map((r) => r.topic.id)
    expect(both).toContain('caja-menor')

    expect(searchTopics(helpTopics, 'cerrar tractor')).toEqual([])
  })

  it('ranks a title match above a passing mention', () => {
    const results = searchTopics(helpTopics, 'contingencia')

    expect(results.length).toBeGreaterThan(1)
    expect(results[0].topic.id).toBe('contingencia')
  })

  it('returns every topic, with its summary, when the query is blank', () => {
    const results = searchTopics(helpTopics, '   ')

    expect(results).toHaveLength(helpTopics.length)
    expect(results[0].snippet).toBe(helpTopics[0].summary)
  })

  it('only searches the topics it is given, so a role cannot find another role’s guide', () => {
    const visible = topicsFor(helpTopics, audiencesOf(teamLead))

    // "contingencia" only ever appears in manager guides.
    expect(searchTopics(helpTopics, 'contingencia').length).toBeGreaterThan(0)
    expect(searchTopics(visible, 'contingencia')).toEqual([])
  })

  it('finds a topic by a keyword that is not in its text', () => {
    // "login" never appears in the Spanish prose, only in the keywords.
    expect(searchTopics(helpTopics, 'login').map((r) => r.topic.id)).toContain('primeros-pasos')
  })
})

describe('the topic catalogue', () => {
  it('has unique ids', () => {
    const ids = helpTopics.map((topic) => topic.id)

    expect(new Set(ids).size).toBe(ids.length)
  })

  it('only links to topics that exist', () => {
    for (const topic of helpTopics) {
      for (const id of topic.related ?? []) {
        expect(topicById(id), `${topic.id} links to a missing topic "${id}"`).toBeDefined()
      }
    }
  })

  it('gives every topic an audience and something to read', () => {
    for (const topic of helpTopics) {
      expect(topic.audiences.length, topic.id).toBeGreaterThan(0)
      expect(topic.sections.length, topic.id).toBeGreaterThan(0)
    }
  })

  it('covers every role', () => {
    for (const user of [superAdmin, admin, manager, teamLead]) {
      expect(topicsFor(helpTopics, audiencesOf(user)).length, `${user.id}`).toBeGreaterThan(0)
    }
  })
})
