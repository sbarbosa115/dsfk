export type ProjectRole = 'PROJECT_MANAGER' | 'TEAM_LEAD'
export type ProjectStatus = 'DRAFT' | 'ACTIVE' | 'COMPLETED' | 'ARCHIVED'

export interface Membership {
  projectId: number
  projectName: string
  role: ProjectRole
}

export interface CurrentUser {
  id: number
  email: string
  fullName: string
  admin: boolean
  /** Admins who may also use "Ver como"; only they can grant it. */
  superAdmin: boolean
  memberships: Membership[]
  /** Set while an admin is viewing the app as this user. */
  impersonator: { id: number; fullName: string } | null
  /** Show the "Ver como" menu (admin, or already impersonating). */
  canImpersonate: boolean
}

export interface User {
  id: number
  email: string
  fullName: string
  admin: boolean
  superAdmin: boolean
  active: boolean
  createdAt: string
  memberships?: Membership[]
}

export interface Member {
  id: number
  role: ProjectRole
  user: Pick<User, 'id' | 'email' | 'fullName'>
}

export interface Project {
  id: number
  name: string
  description: string | null
  currency: string
  status: ProjectStatus
  plannedStart: string | null
  plannedEnd: string | null
  createdAt: string
  members?: Member[]
}

export interface Settings {
  defaultCurrency: string
  teamLeadExpenseLimit: string
  pettyCashLowBalancePercent: number
  budgetWarningPercents: number[]
}

export type BudgetStatus = 'DRAFT' | 'SUBMITTED' | 'RETURNED' | 'APPROVED'
export type StageStatus = 'PENDING' | 'IN_PROGRESS' | 'COMPLETED'

export interface PlanIssue {
  code: 'no_stages' | 'stage_without_lines' | 'milestone_weights'
  stageId?: number
}

export interface BudgetLine {
  id: number
  categoryId: number
  description: string
  unit: string
  quantity: string
  unitPrice: string
  total: string
}

export interface Milestone {
  id: number
  name: string
  /** Basis points: 10000 = 100% */
  weight: number
  plannedDate: string | null
  completedAt: string | null
  completedBy: { id: number; fullName: string } | null
  completionNotes: string | null
  overdue: boolean
}

export interface Stage {
  id: number
  name: string
  position: number
  status: StageStatus
  plannedStart: string | null
  plannedEnd: string | null
  actualStart: string | null
  actualEnd: string | null
  progress: number
  milestoneWeightTotal: number
  milestones: Milestone[]
  // Only for users who can see financials:
  budgetTotal?: string
  weight?: number
  lines?: BudgetLine[]
}

export interface BudgetEvent {
  status: BudgetStatus
  user: { id: number; fullName: string }
  comment: string | null
  createdAt: string
}

export interface Plan {
  project: { id: number; name: string; currency: string; status: ProjectStatus }
  permissions: {
    viewFinancials: boolean
    edit: boolean
    manageCategories: boolean
    submit: boolean
    review: boolean
    track: boolean
    reopenMilestones: boolean
  }
  budgetStatus: BudgetStatus
  progress: number
  stages: Stage[]
  categories: { id: number; name: string }[]
  budget?: {
    contingency: string
    stagesTotal: string
    total: string
    approvedAt: string | null
    byCategory: { categoryId: number; total: string }[]
    events: BudgetEvent[]
  }
  issues?: PlanIssue[]
}

export type LedgerAccount = 'STAGE' | 'PETTY_CASH' | 'CONTINGENCY'
export type MovementType = 'DEPOSIT' | 'CONTINGENCY_DRAW' | 'CARRYOVER' | 'EXPENSE' | 'REIMBURSEMENT'
export type PaymentMethod = 'TRANSFER' | 'CASH' | 'CHECK' | 'OTHER'

export interface StageFinance {
  id: number
  name: string
  status: StageStatus
  budget: string
  deposited: string
  contingencyDraws: string
  carriedIn: string
  carriedOut: string
  received: string
  available: string
  beyondBudget: string
  spent: string
  remainingBudget: string
  /** Basis points of the budget already spent */
  executed: number
  /** Basis points of the budget already received */
  funded: number
  nextStage: string | null
}

export interface Finance {
  currency: string
  budgetApproved: boolean
  permissions: { deposit: boolean; void: boolean; drawContingency: boolean; completeStages: boolean }
  totals: { budget: string; deposited: string; spent: string; stagesAvailable: string; pettyCash: string; contingency: string }
  stages: StageFinance[]
  categories: { id: number; name: string; budget: string; spent: string; executed: number }[]
  contingency: { budgeted: string; deposited: string; carriedIn: string; drawn: string; balance: string }
  pettyCash: { deposited: string; balance: string }
}

export interface Movement {
  id: number
  type: MovementType
  date: string
  amount: string
  method: PaymentMethod | null
  reference: string | null
  note: string | null
  createdBy: { id: number; fullName: string }
  createdAt: string
  voided: { at: string; by: string | null; reason: string | null } | null
  entries: {
    account: LedgerAccount
    stageId: number | null
    stageName: string | null
    categoryId: number | null
    categoryName: string | null
    amount: string
  }[]
  attachments: { id: number; name: string; mimeType: string; size: number }[]
}

export type ExpenseStatus = 'SUBMITTED' | 'PM_APPROVED' | 'APPROVED' | 'REJECTED' | 'REIMBURSED' | 'VOIDED'
export type PaidFrom = 'STAGE' | 'PETTY_CASH' | 'OUT_OF_POCKET'

export interface Attachment {
  id: number
  name: string
  mimeType: string
  size: number
}

export interface Expense {
  id: number
  date: string
  amount: string
  description: string
  supplier: string | null
  invoiceNumber: string | null
  stage: { id: number; name: string }
  category: { id: number; name: string }
  paidFrom: PaidFrom
  paidBy: { id: number; fullName: string }
  status: ExpenseStatus
  rejectionReason: string | null
  reimbursement: { id: number; date: string; method: PaymentMethod } | null
  createdAt: string
  attachments: Attachment[]
  permissions: { edit: boolean; attach: boolean; approve: boolean; reject: boolean; void: boolean; reimburse: boolean }
  events?: {
    type: 'CREATED' | 'EDITED' | 'PM_APPROVED' | 'APPROVED' | 'REJECTED' | 'REIMBURSED' | 'VOIDED'
    user: string
    comment: string | null
    previous: Record<string, string | null> | null
    createdAt: string
  }[]
}

export interface ExpenseList {
  items: Expense[]
  summary: { pendingCount: number; pendingTotal: string; toReimburseCount: number; toReimburseTotal: string; teamLeadLimit: string }
}

export interface PettyCashCycle {
  id: number
  number: number
  status: 'OPEN' | 'CLOSED' | 'SIGNED_OFF'
  openedAt: string
  closedAt: string | null
  closedBy: string | null
  closingNote: string | null
  signedOffAt: string | null
  signedOffBy: string | null
  openingBalance: string
  topUps: string
  spent: string
  reimbursed: string
  closingBalance: string
  movements?: {
    id: number
    type: MovementType
    date: string
    amount: string
    description: string
    user: string
    voided: boolean
    attachments: { id: number; name: string }[]
  }[]
}

export interface PettyCash {
  balance: string
  current: PettyCashCycle
  history: PettyCashCycle[]
  unsignedCount: number
  permissions: { close: boolean; signOff: boolean }
}

export interface DashboardAlert {
  level: 'error' | 'warning' | 'info'
  code: string
  params: Record<string, string | number>
}

export interface ProjectDashboard {
  currency: string
  budgetApproved: boolean
  totals: { budget: string; contingency: string; deposited: string; spent: string; available: string; pettyCash: string }
  progress: number
  plannedProgress: number
  executed: number
  earnedValue: string
  plannedValue: string
  cpi: number | null
  spi: number | null
  forecastAtCompletion: string | null
  varianceAtCompletion: string | null
  stages: {
    id: number
    name: string
    status: StageStatus
    budget: string
    spent: string
    executed: number
    progress: number
    plannedProgress: number
    cpi: number | null
    spi: number | null
    plannedStart: string | null
    plannedEnd: string | null
    actualStart: string | null
    actualEnd: string | null
    delayed: boolean
  }[]
  monthly: { month: string; deposited: string; spent: string }[]
  alerts: DashboardAlert[]
}

export interface PortfolioRow {
  id: number
  name: string
  status: ProjectStatus
  currency: string
  plannedEnd: string | null
  budgetApproved: boolean
  budget: string
  deposited: string
  spent: string
  progress: number
  plannedProgress: number
  executed: number
  cpi: number | null
  spi: number | null
  alerts: number
}

export interface AuditPage {
  total: number
  page: number
  perPage: number
  items: {
    id: number
    projectId: number | null
    user: string | null
    action: 'create' | 'update' | 'delete'
    entityType: string
    entityId: number | null
    changes: Record<string, [unknown, unknown]>
    createdAt: string
  }[]
}
