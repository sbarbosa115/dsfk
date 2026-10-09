import {describe, expect, it} from 'vitest';
import {depositFixture} from '@/entities/finance';
import {movementRowActions} from './rowActions';

const slip = {id: 7, name: 'slip.pdf', mimeType: 'application/pdf', size: 10};

describe('movementRowActions', () => {
  it('asks an admin for the proof of a deposit that has none, voiding last', () => {
    expect(movementRowActions(depositFixture(), true)).toEqual({
      main: 'attach',
      items: [],
      last: ['void'],
    });
  });

  it('opens the proof once there is one, and still lets an admin add another', () => {
    expect(
      movementRowActions(depositFixture({attachments: [slip]}), true),
    ).toEqual({main: 'proof', items: ['attach'], last: ['void']});
  });

  it('shows the PM the proof and nothing to change', () => {
    expect(
      movementRowActions(depositFixture({attachments: [slip]}), false),
    ).toEqual({main: 'proof', items: [], last: []});
  });

  it('offers nothing on a voided deposit without proof', () => {
    const voided = depositFixture({
      voided: {by: 'Ana', at: '2026-10-16', reason: 'Duplicado'},
    });
    expect(movementRowActions(voided, true)).toEqual({
      main: null,
      items: [],
      last: [],
    });
  });

  it('opens the menu from "Más" for a contingency draw an admin can void', () => {
    const draw = depositFixture({type: 'CONTINGENCY_DRAW', method: null});
    expect(movementRowActions(draw, true)).toEqual({
      main: null,
      items: [],
      last: ['void'],
    });
  });
});
