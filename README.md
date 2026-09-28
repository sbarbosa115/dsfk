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
- **Handlers register through marker interfaces** (`CommandHandler`, `EventHandler`) wired in `services.yaml`,
  so the Application layer names no framework class.

## Known gaps

- Per-project setting overrides, milestone evidence files, automatic project status, Excel/PDF export, budget
  templates and a Gantt chart are out of scope (PRD §9).
- The theme choice (Claro/Oscuro/Según el dispositivo) is kept in the browser, not on the user's account.
- Light theme input borders are below 3:1 contrast (inherited from the MDX design; `styles.test.ts` lists it).
- `app:create-admin` asks for the password interactively or prints a generated one; there is no
  non-interactive `--password` option.

## Deployment

Deployed on cPanel by pulling from GitHub and building on the server:

```bash
~/dsfk-src/deploy/cpanel-update.sh          # latest main (or pass a tag, branch or commit)
```

Setup, cron jobs and backups: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
