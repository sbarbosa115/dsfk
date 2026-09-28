# Control de Proyectos

Construction project tracker: budgets, deposits, expenses, caja menor and milestones.
Product: [docs/PRD.md](docs/PRD.md) · detailed rules: [docs/SPEC.md](docs/SPEC.md) · deployment:
[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) · rebuild plan: [docs/pdr/prd-rebuild.md](docs/pdr/prd-rebuild.md).

- `backend/`: Symfony 8.1 (PHP 8.4+) JSON API, MySQL. One folder per bounded context under `src/`, each with
  `Domain`, `Application`, `Infrastructure` and `UI` layers (Deptrac enforces them).
- `backend/assets/react/`: React + TypeScript, UI in Spanish, Feature-Sliced Design (`app`, `pages`,
  `widgets`, `features`, `entities`, `shared`; ESLint enforces the imports). Symfony serves it: Twig renders
  `templates/spa.html.twig` with `react_component('App')` (Symfony UX React) and Webpack Encore builds it into
  `backend/public/build`. The design is the MDX project's: `assets/styles/app.css` (tokens for light and dark,
  checked by `styles.test.ts`) and the kit in `shared/ui` (ListView, row tints with a legend, FilterBar,
  colour-coded actions, FormModal, DateInput, MoneyField).

## Local development (Docker)

```bash
make up                 # php/apache :18081, mysql :13306, mailpit :18025, worker, node (Encore watch)
make install
make migrate
make admin EMAIL=admin@example.com NAME="Administrador"
```

Ports are overridable so the stack can sit next to other Docker projects (another worktree, say): set
`APP_PORT`, `MYSQL_PORT` or `MAILPIT_PORT` in a `.env` file next to `compose.yaml`.

- The app: http://localhost:18081. The `node` service rebuilds the UI on every save (`docker compose logs node`);
  reload the page to see it. `make build` makes the production build.
- Captured emails: http://localhost:18025

## Quality

```bash
make test               # PHPUnit (unit + API against the MySQL test DB) + tsc + Vitest (incl. the colour checks)
make gate               # PHP-CS-Fixer, PHPStan (level 8), Deptrac, Prettier, ESLint (FSD + Google), tsc
make fix                # apply the formatters, then the gate
make api                # regenerate the OpenAPI schema and the UI's TypeScript types
```

Every change must pass `make test` and `make gate` in Docker before it is considered done. The process
(plan, test-first, gate, security audit, browser regression run) is the `symfony-react-app` skill; security
audits live in [docs/security/](docs/security/README.md), the browser suite in
[docs/tests/ui-regression.md](docs/tests/ui-regression.md).

## API reference

All JSON under `/api`, session cookie. Every non-GET call needs `X-Requested-With: XMLHttpRequest` (403
`csrf_header_missing` otherwise). Errors are `{"error": "<code>", "violations": {...}}`; codes are listed per
endpoint. The full contract is `backend/assets/react/shared/api/openapi.json` (`make api`). Lists answer
`{items, total, page, perPage}` and take `?q=`, `page` and `perPage`.

| Endpoint | Who | Answers | Errors |
|---|---|---|---|
| `POST /login` `{email, password}` | anyone | 200 current user | 401 `invalid_credentials`, `account_disabled`, `too_many_attempts` (5 per 15 min) |
| `POST /logout` | signed in | 204 | |
| `GET /me` | signed in | current user: memberships, `impersonator`, `canImpersonate` | 401 `authentication_required` |
| `POST /me/password` `{currentPassword, newPassword}` | signed in | 204, session kept | 422 `validation_failed` |
| `POST /impersonate?_switch_user=<email\|_exit>` | super admin, `IMPERSONATION_ENABLED` | 302 → `GET /impersonate` (current user) | 403 `switch_user_not_allowed` (GET or no CSRF header), 403 (target not allowed) |
| `GET /users?q=&status=active\|inactive\|all` | admin | page of users by name, with project roles | 403 |
| `POST /users` `{email, fullName, password, admin, superAdmin}` | admin | 201 user | 403 `super_admin_required`, 422 `email_taken`, `validation_failed` |
| `PATCH /users/{id}` (fields sent change) | admin | 200 user | 403 `super_admin_required`, 404 `user_not_found`, 422 `email_taken`, `cannot_change_own_access`, `validation_failed` |
| `GET /projects?q=&status=` | signed in | page of projects, newest first: admins all, others their own; each with `myRole` and members | 4xx on an unknown status |
| `POST /projects` `{name, description?, currency?, status?, plannedStart?, plannedEnd?}` | admin | 201 project (currency defaults to Settings) | 403, 422 `validation_failed` |
| `GET /projects/{id}` | member or admin | project with members | 404 `project_not_found` (also when not a member) |
| `PATCH /projects/{id}` (fields sent change; `null` clears a date) | admin | 200 project | 404, 422 `currency_locked`, `validation_failed` |
| `POST /projects/{id}/members` `{userId, role}` | admin | 200 project (adds, or changes the role) | 409 `project_manager_exists`, 422 `admin_is_global`, `user_inactive`, `user_not_found` |
| `DELETE /projects/{id}/members/{memberId}` | admin | 204 | 404 `member_not_found` (also a member of another project) |
| `GET /projects/{id}/plan` | member | the plan: stages with lines and milestones, categories, budget, progress, what blocks submitting, `permissions`; amounts `null` for Team Leads | 404 |
| `POST /projects/{id}/categories` `{name}` · `PATCH\|DELETE /categories/{id}` | PM, admin | the plan (every plan write answers with it) | 409 `category_in_use`, 422 name taken |
| `POST /projects/{id}/stages` · `PATCH\|DELETE /stages/{id}` · `PUT /projects/{id}/stages/order {ids}` | PM, admin | the plan | 409 `budget_locked` (dates after approval, anything while submitted) |
| `POST /stages/{id}/lines` · `PUT\|DELETE /budget-lines/{id}` `{categoryId, description, unit, quantity, unitPrice}` | PM, admin, while editable | the plan (line total = quantity × unit price, half up) | 409 `budget_locked`, 422 |
| `POST /stages/{id}/milestones` · `PATCH\|DELETE /milestones/{id}` `{name, weight %, plannedDate}` | PM, admin, while editable | the plan | 422 weights over 100 % |
| `PUT /projects/{id}/budget/contingency` `{contingency}` | PM, admin, while editable | the plan | 409 `budget_locked` |
| `POST /projects/{id}/budget/submit` | PM, admin | the plan | 422 `budget_incomplete` with `issues` |
| `POST /projects/{id}/budget/{return {comment}\|approve}` | admin | the plan; approving activates the project | 409 `budget_not_submitted` |
| `POST /stages/{id}/start {actualStart}` · `POST /milestones/{id}/complete {completedAt, notes}` | PM, admin, after approval | the plan | 409 `budget_not_approved`, `stage_already_started`, `milestone_already_completed`; 422 future date |
| `POST /milestones/{id}/reopen` | admin | the plan | 409 `stage_completed` |
| `GET /projects/{id}/finance` | PM, admin | funding per stage, category, caja menor and contingency next to the budget, with `permissions` | 403 Team Lead, 404 |
| `GET /projects/{id}/movements?q=` | PM, admin | page of deposits, draws and carry-overs (voided included), newest first; `q` in reference and note | 403, 404 |
| `POST /projects/{id}/deposits` `{date, method, reference?, note?, allocations: [{destination, amount, stageId?, categoryId?}]}` | admin, after approval | 201 movement | 409 `budget_not_approved`; 422 per part (`allocations[1].stageId`: other project's or completed stage), future date |
| `POST /projects/{id}/contingency/draws` `{stageId, amount, date, reason}` | admin, after approval | 201 movement | 409 `budget_not_approved`; 422 `amount` above the contingency balance |
| `POST /movements/{id}/void` `{reason}` | admin | 200 movement, kept with who, when and why | 409 `movement_not_voidable` (carry-overs), `movement_already_voided`, `void_would_overdraw`, `cycle_closed` |
| `POST /movements/{id}/attachments` multipart `file` | admin | 201 movement with its files | 422 `file`: type by content (PDF, JPG, PNG, WEBP, HEIC), 10 MB |
| `GET /attachments/{id}` | PM, admin | the file, inline, `nosniff`, sandboxed | 403 Team Lead, 404 `attachment_not_found`, `file_missing` |
| `POST /stages/{id}/complete` `{actualEnd}` | admin, after approval | the funding summary; the stage's leftover moves to the next open stage, or to the contingency after the last | 409 `stage_not_in_progress`, `stage_milestones_pending` |
| `GET /projects/{id}/expenses?q=&status=A,B&stageId=` | member | page of expenses, newest first (a Team Lead's own only), with `summary` (pending, to reimburse, Team Lead limit) | 404, 422 unknown status |
| `POST /projects/{id}/expenses` `{stageId, categoryId, date, amount, description, supplier?, invoiceNumber?, paidFrom}` | member, after approval | 201 expense: the PM and Admins pay from `STAGE` or `PETTY_CASH` (counts at once), Team Leads are `OUT_OF_POCKET` (SUBMITTED) | 409 `budget_not_approved`; 422 `insufficient_funds` (with `available`), `validation_failed` |
| `GET\|PUT /expenses/{id}` | its Team Lead, PM, admin | the expense with its history; PUT corrects a pending or rejected one (back to SUBMITTED) | 404 `expense_not_found` (also another Team Lead's), 409 `expense_not_editable` |
| `POST /expenses/{id}/approve` · `/reject {reason}` | PM, admin | the expense: APPROVED, or PM_APPROVED above the Team Lead limit (an Admin approves then) | 409 `receipt_required`, `expense_awaiting_admin`, `expense_invalid_status` |
| `POST /expenses/{id}/void {reason}` | admin | the expense, VOIDED; its money goes back | 409 `expense_invalid_status` (reimbursed), `cycle_closed` |
| `POST /expenses/{id}/attachments` multipart `file` | its Team Lead while pending/rejected, PM, admin | 201 expense with its receipts | 403, 422 `file` |
| `POST /projects/{id}/reimbursements` `{expenseIds, date, method, reference?}` | PM, admin | 201 the expenses, REIMBURSED (one caja menor movement) | 422 `expenseIds` (not approved out of pocket, another project's), `insufficient_funds` |
| `GET /projects/{id}/petty-cash` · `GET /petty-cash-cycles/{id}` | PM, admin | balance, current cycle with movements, closed cycles; one cycle | 403 Team Lead, 404 `cycle_not_found` |
| `POST /projects/{id}/petty-cash/close {note?}` | PM, admin | the closed cycle (its balance opens the next) | 409 `cycle_not_open` |
| `POST /petty-cash-cycles/{id}/sign-off` | admin | the signed-off cycle | 409 `cycle_not_closed` |
| `GET /dashboard` | signed in | the projects whose money the person sees (Admins all, a PM theirs, a Team Lead none), each with budget, spent, progress, planned progress, CPI, SPI and its warnings count | |
| `GET /projects/{id}/dashboard` | PM, admin | earned value (EV, PV, CPI, SPI, forecast final cost), per stage, money in and out per month (last 12), and what needs attention | 403 Team Lead, 404 |
| `GET /audit?projectId=&entityType=&q=` | admin | page of the audit trail, newest first, with `entityTypes` for the filter; `q` searches the person | 403 |
| `GET /settings` | signed in | settings | |
| `PUT /settings` (fields sent change) | admin | 200 settings | 403, 422 `validation_failed` |

## Data model decisions

- **The database schema is the contract.** Production holds real data, so the rebuild maps its entities onto
  the existing tables and keeps the existing migrations; new migrations only add. A column that points into
  another bounded context is a plain id marked `#[References]`, and a schema listener re-creates its foreign
  key and index, so `doctrine:migrations:diff` stays empty.
- **Ids are auto-increment and handlers never flush,** so a create handler returns a `NewId`, read after the
  command bus has committed.
- **Only a super admin edits a super admin** (any field): otherwise an ordinary admin could reset a super
  admin's password and sign in as them.
- **Outside a project is 404, not 403.** `ProjectGuard` answers every project-scoped endpoint: someone who is
  not in the project cannot tell it exists; a member whose role does not allow the action gets 403.
- **A project's budget row is created lazily** the first time its plan is written (projects are created in the
  Project context, which knows nothing of budgets); reading a plan with no budget yet shows an empty draft.
- **Pesos show centavos only when there are some** (`$ 35.000,50`, `$ 437.506`), so no amount is ever rounded
  on screen.
- **Money moves only through the ledger.** A movement's entries are signed amounts per account (a stage, the caja
  menor, the contingency); balances are sums of the entries of movements that are not voided, and movements are
  never deleted. Every money write locks the project's row for its transaction, so two writes cannot spend the
  same balance.
- **Expenses move money through Finance in their own transaction.** The Expense context calls Finance's
  `ExpensePayments` (pay, refund, pay back) and reads spending back for the budget comparison; handlers lock the
  expenses they change (`SELECT … FOR UPDATE`), so a void and a reimbursement, or two reimbursements, never both
  pass.
- **Completing a stage is a Planning command that settles the stage's money through Finance** in the same
  transaction; the endpoint answers with the funding summary.
- **The audit trail records every `Audited` entity** (a Shared interface each says its project through) in the
  change's own transaction; while an Admin uses "Ver como", entries name both ("Laura Gómez (vía Administrador)").
  Passwords are masked.
- **Emails follow domain events.** The Notification context handles the other contexts' events after their
  command commits and queues templated emails on Messenger (the cron consumer sends them); a failing handler is
  logged and never turns a saved change into an error. `app:alerts:daily` sends the daily digest.
- **Dashboards are a read side** (Reporting) computing earned value from Planning's stages and Expense's spending;
  each chart can be read as a table.
- **Upload folders are 0750** in production (`UPLOAD_DIR_MODE`); the Docker dev stack uses 0755 so the node
  service's tools can walk the project.
- **Handlers register through marker interfaces** (`CommandHandler`, `EventHandler`) wired in `services.yaml`,
  so the Application layer names no framework class.

## Known gaps

- Per-project setting overrides, milestone evidence files, automatic project status, Excel/PDF export, budget
  templates and a Gantt chart are out of scope (PRD §9).
- A member who is later made an admin keeps their project membership (harmless: admins pass every check), while
  an admin cannot be added as a member.
- The theme choice (Claro/Oscuro/Según el dispositivo) is kept in the browser, not on the user's account.
- Light theme input borders are below 3:1 contrast (inherited from the MDX design; `styles.test.ts` lists it).
- The audit trail starts with this rebuild: changes made before it was deployed are not in it.
- An expense of a closed caja menor cycle still shows Anular; the server refuses it ("…ciclo de caja menor
  cerrado…"), since an expense does not know its cycle's state.
- `app:create-admin` asks for the password interactively or prints a generated one; there is no
  non-interactive `--password` option.

## Deployment

Deployed on cPanel by pulling from GitHub and building on the server:

```bash
~/dsfk-src/deploy/cpanel-update.sh          # latest main (or pass a tag, branch or commit)
```

Setup, cron jobs and backups: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
