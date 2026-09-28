import type {Plan} from '../api/planApi';

/** A two-stage draft plan as the API answers it to a PM, for component tests. */
export function planFixture(overrides: Partial<Plan> = {}): Plan {
  return {
    project: {id: 9, name: 'Torre', currency: 'COP', status: 'DRAFT'},
    permissions: {
      viewFinancials: true,
      edit: true,
      manageCategories: true,
      submit: true,
      review: false,
      track: false,
      reopenMilestones: false,
    },
    budgetStatus: 'DRAFT',
    progress: 0,
    stages: [
      {
        id: 11,
        name: 'Cimentación',
        position: 0,
        status: 'PENDING',
        plannedStart: '2026-10-01',
        plannedEnd: '2026-12-15',
        actualStart: null,
        actualEnd: null,
        progress: 0,
        milestoneWeightTotal: 10000,
        milestones: [
          {
            id: 21,
            name: 'Excavación',
            weight: 4000,
            plannedDate: '2026-10-20',
            completedAt: null,
            completedBy: null,
            completionNotes: null,
            overdue: false,
          },
          {
            id: 22,
            name: 'Vaciado',
            weight: 6000,
            plannedDate: null,
            completedAt: null,
            completedBy: null,
            completionNotes: null,
            overdue: false,
          },
        ],
        budgetTotal: '1437506.25',
        weight: 3239,
        lines: [
          {
            id: 31,
            categoryId: 1,
            description: 'Concreto',
            unit: 'm³',
            quantity: '12.5',
            unitPrice: '35000.50',
            total: '437506.25',
          },
        ],
      },
    ],
    categories: [{id: 1, name: 'Materiales'}],
    budget: {
      contingency: '500000.00',
      stagesTotal: '1437506.25',
      total: '1937506.25',
      approvedAt: null,
      byCategory: [{categoryId: 1, total: '1437506.25'}],
      events: [],
    },
    issues: [],
    ...overrides,
  };
}
