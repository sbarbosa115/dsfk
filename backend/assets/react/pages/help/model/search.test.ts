import {describe, expect, it} from 'vitest';
import type {CurrentUser} from '@/entities/session';
import {helpTopics, topicById} from './content';
import {audiencesOf, normalize, searchTopics, topicsFor} from './search';

const lead = {
  id: 3,
  email: 'lider@x.co',
  fullName: 'Carlos Ruiz',
  admin: false,
  superAdmin: false,
  memberships: [{projectId: 1, projectName: 'Torre', role: 'TEAM_LEAD'}],
  impersonator: null,
  canImpersonate: false,
} as unknown as CurrentUser;
const manager = {
  ...lead,
  id: 2,
  memberships: [{projectId: 1, projectName: 'Torre', role: 'PROJECT_MANAGER'}],
} as unknown as CurrentUser;
const admin = {
  ...lead,
  id: 1,
  admin: true,
  memberships: [],
} as unknown as CurrentUser;
const superAdmin = {
  ...admin,
  id: 0,
  superAdmin: true,
} as unknown as CurrentUser;

describe('audiencesOf', () => {
  it('maps a person to the audiences they belong to', () => {
    expect(audiencesOf(admin)).toEqual(['ADMIN']);
    expect(audiencesOf(superAdmin)).toEqual(['ADMIN', 'SUPER_ADMIN']);
    expect(audiencesOf(lead)).toEqual(['TEAM_LEAD']);
    expect(audiencesOf(null)).toEqual([]);
  });
});

describe('topicsFor', () => {
  it('shows each role its own guides', () => {
    const forLead = topicsFor(helpTopics, audiencesOf(lead)).map((t) => t.id);
    expect(forLead).toContain('registrar-gasto');
    expect(forLead).toContain('seguir-gastos');
    expect(forLead).not.toContain('configuracion');
    expect(forLead).not.toContain('depositos');
    expect(
      topicsFor(helpTopics, audiencesOf(manager)).map((t) => t.id),
    ).toContain('enviar-presupuesto');
    expect(
      topicsFor(helpTopics, audiencesOf(admin)).map((t) => t.id),
    ).toContain('firmar-ciclo');
  });

  it('keeps "Ver como" for super admins', () => {
    expect(
      topicsFor(helpTopics, audiencesOf(superAdmin)).map((t) => t.id),
    ).toContain('ver-como');
    expect(
      topicsFor(helpTopics, audiencesOf(admin)).map((t) => t.id),
    ).not.toContain('ver-como');
  });
});

describe('searchTopics', () => {
  it('finds a guide without accents and ranks the title first', () => {
    const results = searchTopics(helpTopics, 'deposito');
    expect(results[0]!.topic.id).toBe('depositos');
  });

  it('needs every word to match', () => {
    expect(
      searchTopics(helpTopics, 'caja menor cerrar').map((r) => r.topic.id),
    ).toContain('caja-menor');
    expect(searchTopics(helpTopics, 'caja menor zzzz')).toEqual([]);
  });

  it('says why a guide matched', () => {
    const [result] = searchTopics(helpTopics, 'recibo');
    expect(normalize(result!.snippet)).toContain('recibo');
  });

  it('only links guides that exist', () => {
    for (const topic of helpTopics) {
      for (const id of topic.related ?? []) {
        expect(topicById(id), `${topic.id} → ${id}`).toBeDefined();
      }
    }
  });
});
