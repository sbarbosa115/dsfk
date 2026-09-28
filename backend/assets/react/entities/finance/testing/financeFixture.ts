import type {Finance, Movement} from '../api/financeApi';

/** An approved two-stage project as the API answers it to an Admin, for component tests. */
export function financeFixture(overrides: Partial<Finance> = {}): Finance {
  return {
    currency: 'COP',
    budgetApproved: true,
    permissions: {
      deposit: true,
      void: true,
      drawContingency: true,
      completeStages: true,
    },
    totals: {
      budget: '4937506.25',
      deposited: '2000000.00',
      spent: '0.00',
      stagesAvailable: '1500000.00',
      pettyCash: '300000.00',
      contingency: '200000.00',
    },
    stages: [
      {
        id: 11,
        name: 'Cimentación',
        status: 'IN_PROGRESS',
        budget: '1437506.25',
        deposited: '1500000.00',
        contingencyDraws: '0.00',
        carriedIn: '0.00',
        carriedOut: '0.00',
        received: '1500000.00',
        available: '1500000.00',
        beyondBudget: '62493.75',
        spent: '0.00',
        remainingBudget: '1437506.25',
        executed: 0,
        funded: 10435,
        nextStage: 'Estructura',
      },
      {
        id: 12,
        name: 'Estructura',
        status: 'PENDING',
        budget: '3000000.00',
        deposited: '0.00',
        contingencyDraws: '0.00',
        carriedIn: '0.00',
        carriedOut: '0.00',
        received: '0.00',
        available: '0.00',
        beyondBudget: '0.00',
        spent: '0.00',
        remainingBudget: '3000000.00',
        executed: 0,
        funded: 0,
        nextStage: null,
      },
    ],
    categories: [
      {
        id: 3,
        name: 'Materiales',
        budget: '3437506.25',
        spent: '0.00',
        executed: 0,
      },
      {id: 4, name: 'Nómina', budget: '1000000.00', spent: '0.00', executed: 0},
    ],
    contingency: {
      budgeted: '500000.00',
      deposited: '200000.00',
      carriedIn: '0.00',
      drawn: '0.00',
      balance: '200000.00',
    },
    pettyCash: {deposited: '300000.00', balance: '300000.00'},
    ...overrides,
  };
}

/** The deposit behind financeFixture(). */
export function depositFixture(overrides: Partial<Movement> = {}): Movement {
  return {
    id: 51,
    type: 'DEPOSIT',
    date: '2026-10-15',
    amount: '2000000.00',
    method: 'TRANSFER',
    reference: 'TRX-001',
    note: null,
    createdBy: {id: 1, fullName: 'Ana Admin'},
    createdAt: '2026-10-15T10:00:00-05:00',
    voided: null,
    entries: [
      {
        account: 'STAGE',
        stageId: 11,
        stageName: 'Cimentación',
        categoryId: 3,
        categoryName: 'Materiales',
        amount: '1500000.00',
      },
      {
        account: 'PETTY_CASH',
        stageId: null,
        stageName: null,
        categoryId: null,
        categoryName: null,
        amount: '300000.00',
      },
      {
        account: 'CONTINGENCY',
        stageId: null,
        stageName: null,
        categoryId: null,
        categoryName: null,
        amount: '200000.00',
      },
    ],
    attachments: [],
    ...overrides,
  };
}
