# Project Tracker — Functional & Technical Specification

> Status: draft v1 — 2026-09-18
> UI language: Spanish (es-CO). Code, database, API and docs: English.

## 1. Purpose

Track multiple projects (initially building construction) split into dynamic stages, each with a
locked budget, deposits of money from the owner, expenses, milestones and timeline. The owner must
see at any moment where the money went and how the project is doing against its budget.

## 2. Roles

| Role | Scope | Summary |
|---|---|---|
| `ADMIN` (owner) | Global | Creates projects, approves budgets, deposits money, approves over-limit expenses, signs off caja menor cycles, manages users and settings. Sees everything. |
| `PROJECT_MANAGER` | Assigned projects | Drafts the budget, manages stages and milestones, records expenses (stage balance or caja menor), holds the project's caja menor, approves and reimburses Team Lead expenses. |
| `TEAM_LEAD` | Assigned projects | Records expenses paid out of pocket, uploads receipts, sees their own expenses and what they are owed. |

Roles are assigned per project (`ProjectMember`), except `ADMIN`, which is global.

### Permission matrix

| Action | Admin | PM | Team Lead |
|---|:-:|:-:|:-:|
| Create project / assign members | ✅ | — | — |
| Create/edit stages, categories, budget lines (draft) | ✅ | ✅ | — |
| Submit budget for approval | ✅ | ✅ | — |
| Approve / return budget | ✅ | — | — |
| Create/complete milestones | ✅ | ✅ | — |
| Register deposits and splits | ✅ | — | — |
| Draw from contingency | ✅ | — | — |
| Record expense paid from stage balance | ✅ | ✅ | — |
| Record expense paid from caja menor | ✅ | ✅ | — |
| Submit out-of-pocket expense for reimbursement | — | — | ✅ |
| Approve/reject Team Lead expense (≤ limit) | ✅ | ✅ | — |
| Approve Team Lead expense (> limit) | ✅ | ✅ first, then Admin | — |
| Reimburse from caja menor | — | ✅ | — |
| Close caja menor cycle | — | ✅ | — |
| Sign off caja menor cycle | ✅ | — | — |
| Mark stage as completed | ✅ | — | — |
| Settings, users | ✅ | — | — |
| View project financial dashboard | ✅ | ✅ | — |
| View own expenses / amount owed | ✅ | ✅ | ✅ |

## 3. Domain concepts

### 3.1 Project
`name, description, currency (default from settings, fixed after creation), status (DRAFT | ACTIVE | COMPLETED | ARCHIVED), planned_start, planned_end, actual_start, actual_end`

### 3.2 Stage
Dynamic per project, ordered. `name, position, planned_start, planned_end, actual_start, actual_end, status (PENDING | IN_PROGRESS | COMPLETED)`

### 3.3 Category
Defined per project (e.g. Payroll, Materials, Equipment, Subcontracts). No templates in v1.

### 3.4 Budget
- One budget per project, made of **BudgetLines**: `stage, category, description, unit, quantity, unit_price, total`.
- Lifecycle: `DRAFT → SUBMITTED → APPROVED` (or `SUBMITTED → RETURNED → DRAFT` with admin comment).
- The PM drafts it, the Admin approves it. **Once approved it is locked and never changes.** Spending may exceed it; overruns are flagged, never hidden.
- Contingency amount is part of the budget but tracked separately (see 3.7).

### 3.5 Milestones and progress (weighted)
- Each stage has milestones: `name, weight, planned_date, completed_at, completed_by, notes, evidence files`.
- Milestone weights within a stage sum to 100%.
- Stage progress % = sum of weights of completed milestones.
- Stage weight within the project = stage budget / total project budget (excluding contingency).
- Project progress % = Σ(stage progress × stage weight).

### 3.6 Deposits and splits
- Only the Admin registers deposits: `date, amount, method (TRANSFER | CASH | CHECK | OTHER), reference, proof file, notes`.
- Each deposit is **split** into one or more **allocations**. The allocations must add up to the deposit amount. Each allocation goes to one destination:
  - `STAGE` (with optional category as an earmark)
  - `PETTY_CASH` (the project's caja menor)
  - `CONTINGENCY`
- Money availability is tracked **per stage**. Categories are used for the budget-vs-actual breakdown.

### 3.7 Contingency
- One contingency fund per project (budgeted amount + funded amount).
- Only the Admin can draw from it: `amount, target stage, reason`. Draws are shown separately, so it stays visible when an overrun was covered by contingency.

### 3.8 Stage funding, overruns and carry-over
- `Stage available = allocations + contingency draws + carry-in − expenses paid from stage − transfers to petty cash`.
- A stage must be completed: if it needs more money, the Admin keeps depositing. The dashboard shows **Budget**, **Funded**, **Spent**, and **Funded beyond budget** per stage.
- When the Admin marks a stage `COMPLETED`, any remaining balance is carried over automatically to the next stage as a `Carryover` ledger entry. A stage never carries a deficit, because extra money comes from new deposits.

### 3.9 Expenses
`project, stage, category, budget_line (optional), date, amount, description, supplier, invoice_number, receipt files, paid_from (STAGE | PETTY_CASH | OUT_OF_POCKET), paid_by (user), status, created_by`

Status flow:
```
PM / Admin expense:      APPROVED (immediately)
Team Lead expense:       SUBMITTED → APPROVED → REIMBURSED
                                   ↘ REJECTED (reason) → edited → SUBMITTED (versions kept)
Above Team Lead limit:   SUBMITTED → PM_APPROVED → APPROVED (admin) → REIMBURSED
```
- An expense **counts against the budget when APPROVED**.
- Cash leaves the caja menor when the expense is **REIMBURSED** (Team Lead) or recorded (PM paying from caja menor).
- Expenses are never hard-deleted. Corrections are made as new versions, and every change is audited.
- Payroll is recorded as a normal expense in the Payroll category (amounts only).

### 3.10 Caja menor (petty cash)
- One per project, held by the project's PM.
- Funded from deposit allocations (`PETTY_CASH`).
- Works in **cycles**: `OPEN → CLOSED (by PM) → SIGNED_OFF (by Admin)`.
  - The PM closes a cycle when it runs out, or earlier to ask for a top-up.
  - Closing generates a summary: opening balance, top-ups, each expense with its receipt, and the closing balance.
  - The remaining balance rolls into the next cycle.
  - A new top-up is allowed even if the previous cycle isn't signed off yet; the Admin sees a warning.
- **Reimbursement**: the PM reimburses one or more approved Team Lead expenses in a single operation (`date, method, reference`).

### 3.11 Audit log
Every create/update/state change on money-related entities stores: user, timestamp, entity, before/after values.

## 4. Settings (Admin UI)

| Setting | Default | Scope |
|---|---|---|
| Default currency | COP | Global (applies to new projects) |
| Team Lead per-expense limit | 500.000 COP | Global, project override |
| Caja menor low-balance threshold | 20% of last top-up | Global, project override |
| Budget warning thresholds | 80%, 100% | Global, project override |

## 5. Email alerts (PM and Admin)

| Event | To |
|---|---|
| Budget submitted / approved / returned | Admin / PM |
| Team Lead expense submitted | PM (Admin if over limit) |
| Expense rejected | Team Lead |
| Stage or category reaches the warning threshold / exceeds budget | Admin, PM |
| Caja menor below threshold / cycle closed | Admin, PM |
| Milestone overdue (daily check) | Admin, PM |

## 6. Dashboards

- **Portfolio (Admin)**: every project with progress %, budget, funded, spent, and CPI.
- **Project**:
  - Budget vs actual per stage and category.
  - Progress vs spend (earned value: `EV = budget × progress%`, `CPI = EV / actual cost`, `SPI = EV / planned value`).
  - Funding by stage: budget, funded, spent, available, funded beyond budget.
  - Contingency: budgeted, funded, drawn.
  - Caja menor: current balance and cycle.
  - Money owed to Team Leads.
  - Timeline with planned vs actual dates.
- **Deposit ledger**: every deposit with its splits and proof.
- **Team Lead view**: my expenses, their status, and what I'm owed.

## 7. Technical architecture

### 7.1 Constraints
- Production runs on **shared hosting with cPanel**: no Docker, no Node runtime, no long-running workers.
- Local development and testing run in **Docker**. Every change is tested in Docker before it is considered done.

### 7.2 Stack
- **Backend**: Symfony 8.1 (PHP ≥ 8.4.1), Doctrine ORM + Migrations, Symfony Security (session-cookie auth, same origin), Validator, Serializer, Mailer, Messenger.
- **Database**: MySQL/MariaDB. Avoid engine-specific features so it runs on whatever the host provides.
- **Money**: stored as `BIGINT` in minor units, using `brick/money` in PHP. Never floats.
- **Frontend**: React + TypeScript + Vite, React Router, TanStack Query, react-i18next (Spanish), `Intl` formatting with the es-CO locale, a component library, and Recharts. Built locally into `public/app/` and served by Symfony, which avoids CORS and needs no Node on the server.
- **Files**: stored outside the web root (`var/uploads`) and served through a controller that checks permissions.
- **Async/cron**: emails go through the Messenger Doctrine transport, consumed by a cPanel cron job (`messenger:consume --time-limit=50` every minute). Daily cron for overdue-milestone checks.

### 7.3 Local Docker environment
`php` (Apache + mod_php, similar to cPanel, with `.htaccess`), `mysql`, `node` (Vite dev server/build), `mailpit` (captures emails).

### 7.4 Testing
- Backend: PHPUnit unit tests (money rules, balances, state machines) and functional API tests against a test database in Docker.
- Frontend: Vitest + Testing Library.
- A change is "done" only when the test suite passes in Docker.

### 7.5 Deployment (cPanel)
See [DEPLOYMENT.md](DEPLOYMENT.md). On the server, `deploy/cpanel-update.sh` fetches a branch, tag or commit from GitHub, builds the frontend and a production `vendor/` on PHP 8.4, and copies the release into the app folder. Then `deploy/update.sh` backs up, removes files dropped from the release, warms the cache, migrates and runs `app:doctor`.

## 8. Delivery phases

| Phase | Scope |
|---|---|
| 0 | Docker environment, Symfony + React skeleton, auth, users, roles, project membership, settings |
| 1 | Projects, stages, categories, budget lines, milestones, budget approval workflow |
| 2 | Deposits + splits, contingency, stage balances, carry-over, deposit ledger |
| 3 | Expenses (stage balance), receipts, caja menor, Team Lead flow, approvals, reimbursements, cycles |
| 4 | Dashboards (budget vs actual, earned value, funding), email alerts, audit log UI |
| 5 | cPanel deployment guide and first production release |

Future: Excel/PDF export, budget templates, Gantt chart, forecast at completion.

## 9. Owner decisions
Confirmed by the owner on 2026-09-18:
1. Contingency is one fund per project and only the Admin draws from it.
2. Money availability is tracked per stage; categories are earmarks and used for reporting, not separate balances.
3. Only the Admin marks a stage as completed, which triggers the carry-over.

Adopted defaults, not explicitly confirmed:
4. Only the Admin creates projects and assigns members.
5. The PM's own expenses count immediately (no approval needed).

Still open: hosting details (PHP version, MySQL or MariaDB, SSH access).

## 10. Planning rules (implemented in phase 1)
- The budget can be submitted or approved only when every stage has at least one budget line and its milestone weights add up to exactly 100%.
- While the budget is `DRAFT` or `RETURNED`, the PM (or Admin) can edit stages, lines, milestones and contingency. While it is `SUBMITTED` or `APPROVED`, nobody can, the Admin included.
- After approval:
  - Stage names can still be corrected. Planned dates, lines, milestone weights and contingency cannot.
  - Categories can still be added, so unplanned costs have somewhere to go.
  - A category can be deleted only if nothing uses it.
- Milestones can be marked as met only after approval, with a completion date that is not in the future. Only the Admin can reopen a completed milestone.
- Approving the budget moves the project from `DRAFT` to `ACTIVE`.
- Team Leads see stages and milestones but no money figures.
- Budget transitions are recorded as events: who did it, when, and the Admin's comment when returning a budget.
- Line total = quantity (up to 3 decimals) × unit price, rounded half-up to the currency's minor unit.
- Budget transitions are simple methods on the entity; the Symfony Workflow component was not needed.

## 11. Funding rules (implemented in phase 2)
- **Ledger.** Each money movement (`DEPOSIT`, `CONTINGENCY_DRAW`, `CARRYOVER`) is a `FundMovement` with signed `LedgerEntry` rows per account: a stage, `PETTY_CASH` or `CONTINGENCY`. Balances are always computed from non-voided entries and are never stored.
- **Deposits.**
  - Only the Admin records deposits, and only once the budget is approved.
  - The date can't be in the future.
  - A deposit is split into one or more allocations: stage (with an optional category earmark), caja menor or contingency. The deposit amount is the sum of its allocations.
  - Completed stages can't receive money.
- **Proof of payment.** PDF, JPG, PNG, WEBP or HEIC, up to 10 MB. The file type is checked from the content, not from what the browser claims. Files are stored in `var/uploads/<project>/` under random names. Admin and PM can download them; Team Leads can't.
- **Voiding.**
  - Movements are never deleted. The Admin voids them with a reason, and they stay in the history.
  - Voiding is refused if it would leave any account below zero, e.g. when the money was already used or moved.
  - Carry-overs can't be voided.
- **Contingency draws.** Admin only, capped at the contingency's available balance, into a stage that isn't finished, with a required reason.
- **Closing a stage.**
  - Admin only. The stage must be in progress and all its milestones met.
  - Its remaining balance moves to the next unfinished stage. If there is none, it goes to the contingency.
- **Per-stage figures.** For each stage the app shows: budget, received (deposits + contingency draws + money moved in), available, amount beyond budget, and % funded. Team Leads don't see finances.
- **Deployment note.** `var/uploads/` must survive deploys and be included in backups.

## 12. Expense and caja menor rules (implemented in phase 3)
- **Who pays how.** The PM and Admin record expenses paid from **stage funds** or the **caja menor**. These count immediately. Team Leads record **out-of-pocket** expenses only.
- **No overdrafts.** Paying from an account requires enough money in it; otherwise the API returns `insufficient_funds` with the available amount. Reimbursements follow the same rule against the caja menor.
- **Budget vs cash.**
  - An expense counts against the budget (its stage and category) once `APPROVED`, and stays counted after `REIMBURSED`.
  - Cash leaves the stage or caja menor when the expense is paid, or when the Team Lead is reimbursed.
- **Team Lead flow.**
  - `SUBMITTED` → PM approves → `APPROVED`. Above the limit set in Settings, the PM's approval makes it `PM_APPROVED` and the Admin gives the final approval.
  - Approving requires at least one receipt.
  - Rejecting requires a reason. The Team Lead corrects the expense and it goes back to `SUBMITTED`. Every edit stores the previous values in the history.
- **Reimbursements.** The PM (or Admin) reimburses one or more approved Team Lead expenses in one operation from the caja menor, with date, payment method and reference.
- **Voiding.** Admin only, with a reason, for approved expenses not yet reimbursed. The money goes back to its account.
- **Visibility.**
  - Team Leads see and download only their own expenses and receipts.
  - Their summary shows what is pending and what they are owed.
  - Managers see everything, including counts of pending approvals and pending reimbursements.
- **Late expenses.** A completed stage can't be charged to its stage funds, but late caja menor or Team Lead expenses may still be assigned to it.
- **Caja menor cycles.**
  - Every caja menor movement belongs to the open cycle. The first cycle is created on first use.
  - The PM or Admin closes the cycle, which takes a snapshot of the closing balance and opens the next cycle with that balance.
  - The Admin signs closed cycles off. Unsigned cycles are flagged, but they don't block new top-ups.
  - Movements in a closed cycle can't be voided.
- **Finance view.** Adds spent, remaining budget and % executed per stage, and budget vs actual per category.

## 13. Monitoring (implemented in phase 4)
- **Dashboards.**
  - The portfolio view (`/dashboard`) is the home page for Admins and PMs. Each project card shows budget, spent, real vs planned progress, CPI, SPI and an alert count.
  - The project dashboard tab shows spent vs budget, progress vs plan, CPI/SPI with a health label, forecast at completion, current alerts, a progress vs spend chart per stage, deposits vs spending per month, and the stage timeline.
  - Every chart has a table view.
- **Earned value.**
  - BAC = stage budgets (contingency excluded). AC = approved spending. EV = BAC × weighted-milestone progress.
  - PV = the share of each stage that should be done today. If every milestone in the stage has a planned date, PV uses those dates. Otherwise it assumes steady progress between the stage's planned start and end dates.
  - CPI = EV/AC, SPI = EV/PV, EAC = BAC/CPI. Health levels: ≥ 1 good, 0.9–1 warning, < 0.9 critical.
- **Emails** (Spanish, HTML):
  - They are buffered during a request and sent only if it succeeds, through the Messenger queue.

  | Trigger | Recipients |
  |---|---|
  | Budget submitted | Admins |
  | Budget returned or approved | PM |
  | Team Lead expense submitted or resubmitted | PM |
  | Expense above the limit, approved by the PM | Admins |
  | Expense rejected, or expenses reimbursed | The Team Lead |
  | Caja menor cycle closed | Admins |
  | Stage or category spending crosses a warning threshold (only the highest threshold crossed is sent) | Admins + PM |
  | Caja menor drops below the low-balance % of the last top-up | Admins + PM |
  | Daily digest (`app:alerts:daily`): overdue milestones, pending approvals and reimbursements, unsigned cycles | Admins + PM of each active project |
- **Audit log.** A Doctrine listener records create, update and delete on planning, money, user and settings entities: who, when, and before/after values (passwords masked). Admins view it at `/audit`, filtered by project and record type.
- **cPanel cron jobs:**
  - every minute: `php bin/console messenger:consume async --time-limit=50 --memory-limit=128M -q`
  - daily at 07:00: `php bin/console app:alerts:daily -q`
