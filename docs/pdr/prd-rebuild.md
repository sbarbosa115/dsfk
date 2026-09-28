# Plan — rebuild Control de Proyectos (DDD + FSD)

> 2026-09-28 · branch `feature/rebuild` · product: [../PRD.md](../PRD.md) · rules: [../SPEC.md](../SPEC.md)

## What and for whom

Rebuild the whole app described in the PRD, for the same users (Admin, super admin, PM, Team Lead), with
the same behaviour and API, on the `symfony-react-app` process: bounded contexts with hexagonal layers on the
backend, Feature-Sliced Design on the frontend, OpenAPI-generated UI types, and the static-analysis, security
and regression gates. The old implementation (`origin/main` at `2c70a0d`) is the reference for rules the PRD
summarises; nothing is copied without meeting today's bar.

## Decisions

- **The database schema is the contract.** Production (cPanel) holds real data, so the existing tables,
  columns and the six migrations stay. Entities of the new contexts map onto the same tables; new migrations
  only add. Cross-context references are plain id columns, and a Shared schema listener re-declares their
  foreign keys and indexes (same generated names), so `doctrine:migrations:diff` stays empty.
- **The API stays the same** (paths, payloads, problem codes), so the PRD's API table still holds.
- **The Docker stack stays Apache + mod_php** (like cPanel) instead of nginx; a `worker` service is added
  for the Messenger queue in dev. Production keeps the cron-driven consumer.
- **Deployment scripts** (`deploy/`) are kept and extended for the new build steps only if they need it.

## Bounded contexts (backend `src/`)

| Context | Owns | Tables |
|---|---|---|
| `Shared` | money, clock, ids, `DomainError` kinds, command/event buses, API plumbing (CSRF, errors, JSON input, SPA), schema listener | — |
| `Identity` | users, login/throttling, password, super admin, impersonation | `user` |
| `Settings` | global settings | `setting` |
| `Project` | projects, memberships, project access (voter) | `project`, `project_member` |
| `Planning` | stages, categories, budget + events, lines, milestones, progress | `stage`, `category`, `budget`, `budget_event`, `budget_line`, `milestone` |
| `Finance` | ledger, deposits, contingency draws, carry-over, voids, caja menor cycles, balances | `fund_movement`, `ledger_entry`, `petty_cash_cycle` |
| `Expense` | expenses and history, approvals, reimbursements | `expense`, `expense_event`, `reimbursement` |
| `Document` | attachments: upload checks, storage, download access | `attachment` |
| `Reporting` | portfolio and project dashboards, earned value (read side) | — |
| `Notification` | alert emails and the daily digest (event handlers, queue) | `messenger_messages` |
| `Audit` | audit trail listener and browsing | `audit_log` |

Cross-context writes in one transaction go through the other context's `Application` port (e.g. completing a
stage asks `Finance` to carry its balance over; paying an expense asks `Finance` to post the movement).

## Frontend `frontend/src/` (FSD)

`app/` (providers, router, shell) · `pages/` (login, dashboard, projects, project-detail, users, settings,
audit, help) · `widgets/` (plan board, finance panels, expense list, petty-cash panel, project dashboard,
portfolio) · `features/` (one per action: create project, submit budget, record deposit, approve expense…) ·
`entities/` (user, project, stage, budget, movement, expense, cycle, attachment) · `shared/` (api client with
generated types, ui kit, i18n `es`, format, charts).

## Delivery (sub-branches merged into `feature/rebuild`)

| # | Sub-branch | Scope |
|---|---|---|
| 0 | `feature/rebuild-foundation` | tooling (PHP-CS-Fixer, PHPStan, Deptrac, NelmioApiDoc, ESLint+FSD, Prettier, openapi-typescript), `Shared`, worker service, CI, empty gate green |
| 1 | `feature/rebuild-identity` | `Identity`, `Settings`, app shell, login, users, settings, impersonation |
| 2 | `feature/rebuild-projects` | `Project`: projects, members, access |
| 3 | `feature/rebuild-planning` | `Planning`: plan tab, budget workflow, milestones, progress |
| 4 | `feature/rebuild-finance` | `Finance` (funding) + `Document`: deposits, draws, carry-over, voids, proofs, finance tab |
| 5 | `feature/rebuild-expenses` | `Expense` + caja menor cycles, reimbursements, receipts, expenses and caja menor tabs |
| 6 | `feature/rebuild-monitoring` | `Reporting`, `Notification`, `Audit`: dashboards, emails, daily digest, audit page |
| 7 | `feature/rebuild-ops` | help center, seed data, `app:create-admin`, `app:doctor`, regression baseline, README, deploy check |

## Verification

Each phase: unit tests for domain rules, functional API tests per endpoint (happy path per role, 403 wrong
role, 404 for a project the user is not a member of, 409/422 refusals), Vitest + Testing Library for UI logic,
Playwright for sign-in and a deposit → expense → reimbursement flow. The whole suite runs in Docker. Every phase
ends with the gate, a security audit file and a regression run; phase 7 records the baseline suite covering
every area in the PRD.

## Left out (Known gaps, as in PRD §9)

Per-project setting overrides, milestone evidence files, automatic project status, Excel/PDF export, budget
templates, Gantt chart.
