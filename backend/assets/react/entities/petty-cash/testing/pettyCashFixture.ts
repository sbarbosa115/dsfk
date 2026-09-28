import type {Cycle, PettyCash} from '../api/pettyCashApi';

export function cycleFixture(overrides: Partial<Cycle> = {}): Cycle {
  return {
    id: 1,
    number: 1,
    status: 'OPEN',
    openedAt: '2026-10-15T10:00:00-05:00',
    closedAt: null,
    closedBy: null,
    closingNote: null,
    signedOffAt: null,
    signedOffBy: null,
    openingBalance: '0.00',
    topUps: '300000.00',
    spent: '45000.00',
    reimbursed: '0.00',
    closingBalance: '255000.00',
    movements: [
      {
        id: 51,
        type: 'DEPOSIT',
        date: '2026-10-15',
        amount: '300000.00',
        description: 'Fondo inicial',
        user: 'Ana Admin',
        voided: false,
        attachments: [],
      },
      {
        id: 52,
        type: 'EXPENSE',
        date: '2026-10-15',
        amount: '-45000.00',
        description: 'Clavos',
        user: 'Laura Gómez',
        voided: false,
        attachments: [
          {id: 9, name: 'recibo.jpg', mimeType: 'image/jpeg', size: 10},
        ],
      },
    ],
    ...overrides,
  };
}

/** The caja menor as its PM sees it: an open cycle and one closed, waiting for sign-off. */
export function pettyCashFixture(
  overrides: Partial<PettyCash> = {},
): PettyCash {
  return {
    currency: 'COP',
    balance: '255000.00',
    current: cycleFixture({id: 2, number: 2}),
    history: [
      cycleFixture({
        id: 1,
        status: 'CLOSED',
        closedAt: '2026-10-10T10:00:00-05:00',
        closedBy: 'Laura Gómez',
        closingNote: 'Se acabó',
        movements: null,
      }),
    ],
    unsignedCount: 1,
    permissions: {close: true, signOff: false},
    ...overrides,
  };
}
