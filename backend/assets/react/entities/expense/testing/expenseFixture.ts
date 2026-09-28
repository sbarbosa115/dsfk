import type {Expense, ExpensePage} from '../api/expenseApi';

/** A Team Lead's expense waiting for the PM, as the PM sees it. */
export function expenseFixture(overrides: Partial<Expense> = {}): Expense {
  return {
    id: 71,
    date: '2026-10-15',
    amount: '120000.00',
    description: 'Cemento',
    supplier: 'Ferretería El Tornillo',
    invoiceNumber: 'F-100',
    stage: {id: 11, name: 'Cimentación'},
    category: {id: 3, name: 'Materiales'},
    paidFrom: 'OUT_OF_POCKET',
    paidBy: {id: 5, name: 'Carlos Pérez'},
    status: 'SUBMITTED',
    rejectionReason: null,
    reimbursement: null,
    createdAt: '2026-10-15T10:00:00-05:00',
    attachments: [
      {id: 8, name: 'factura.pdf', mimeType: 'application/pdf', size: 1000},
    ],
    permissions: {
      edit: false,
      attach: true,
      approve: true,
      reject: true,
      void: false,
      reimburse: false,
    },
    events: null,
    ...overrides,
  };
}

export function expensePageFixture(
  items: Expense[],
  summary: Partial<ExpensePage['summary']> = {},
): ExpensePage {
  return {
    items,
    total: items.length,
    page: 1,
    perPage: 50,
    summary: {
      pendingCount: 1,
      pendingTotal: '120000.00',
      toReimburseCount: 0,
      toReimburseTotal: '0.00',
      teamLeadLimit: '500000.00',
      ...summary,
    },
  };
}
