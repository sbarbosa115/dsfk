# Portfolio and project list

**Purpose:** See every project at a glance (`/dashboard`) and find, create or open a project (`/projects`).
**Roles:** Admin: sees all projects, creates and edits them. PM: same views for the projects they belong to, no
"Nuevo proyecto". Team Lead: no `/dashboard` (no sidebar link; `/` sends them to `/projects`), sees only their
projects in `/projects`.
**Last updated:** 2026-10-07 (run `issues/runs/2026-10-07-admin-ui.md`)

## Views

### Tablero (portfolio) · `/dashboard`

Shows: header "Tablero" / "Cómo van las obras: presupuesto, gasto, avance y lo que necesita atención." and one card
per project whose finances the user may see (`VIEW_FINANCIALS`): name, project status badge, "Presupuesto",
"Gastado" (amount · % executed), "Avance" progress bar, "Según el plan debería ir en X%", "CPI" and "SPI" health
badges ("0,95 · Atención"; "Sin datos" when null), and "N alerta(s)" when there are alerts.
States: loading: "Cargando…" · empty: "Aún no hay proyectos con dinero que puedas ver." · error: error box with
"Reintentar" · no permission: Team Leads get an empty list from the API (the page is not linked for them).

| Action | Who | Result |
|---|---|---|
| Click a card | Admin, PM | Opens `/projects/:id?tab=dashboard`. |

Health levels: index ≥ 1 "Bien", 0.9–1 "Atención", < 0.9 "Crítico".

### Proyectos · `/projects`

Shows: header "Proyectos" / "Las obras que sigues: …", a filter bar (search "Buscar por nombre…", "Estado":
Todos / Borrador / Activo / Finalizado / Archivado), "Nuevo proyecto" (Admin), a row-colour legend, and a table:
"Nombre" (+ description), "Fechas planeadas", "Moneda", "Mi rol" ("Administrador" for admins), and an "Abrir" eye
icon. Paged ("Página X de Y", "Anterior"/"Siguiente").
States: loading: "Cargando…" · empty with filters: "Ningún proyecto coincide con la búsqueda." + "Ver todos" ·
empty, Admin: "Aún no hay proyectos. Crea el primero para armar su plan y presupuesto." + "Nuevo proyecto" ·
empty, member: "Todavía no estás en ningún proyecto. El administrador te agrega cuando empiece una obra." ·
error: error box with "Reintentar".

| Action | Who | Result |
|---|---|---|
| Search / Estado filter | all | Reloads the list (search waits 300 ms after typing). |
| Abrir (eye) | all | `/projects/:id` (opens on the "Presupuesto y plan" tab). |
| Nuevo proyecto | Admin | Opens the **Nuevo proyecto** modal. |

### Nuevo proyecto / Editar proyecto (modal)

Opened from "Nuevo proyecto" on `/projects`, or "Editar proyecto" in the header of `/projects/:id` (Admin only).
Fields: "Nombre" (required), "Descripción" (opcional), "Moneda" (3 letters, pre-filled with the default currency
from Configuración; hint "…no se puede cambiar después"; **disabled when editing**), "Estado" (select with the 4
statuses; the hint under it explains the chosen one), "Inicio planeado" and "Fin planeado" (opcional, dd/mm/aaaa
with a calendar). Buttons "Cancelar" and "Crear" (new) / "Guardar" (edit).

| Action | Who | Result |
|---|---|---|
| Crear | Admin | `POST /api/projects`; closes and opens the new project's page. |
| Guardar | Admin | `PATCH /api/projects/:id`; closes; header and Resumen update. |
| Invalid input | Admin | Errors under the fields (see Rules). |

## Flows

### Create a project and start its plan

1. `/projects` → "Nuevo proyecto" → Nombre "Prueba QA", leave Moneda (COP), Estado "Borrador" → "Crear".
2. → lands on `/projects/<new id>`, tab "Presupuesto y plan", empty plan ("Aún no hay etapas…").
3. Back to `/projects` → the project is listed with "Mi rol: Administrador", Borrador tone.

## Rules

- Validation: name required, ≤ 150 chars; description ≤ 5000; currency a valid ISO 4217 code; planned end not
  before planned start; dates optional.
- Business: project statuses `DRAFT` (Borrador) · `ACTIVE` (Activo) · `COMPLETED` (Finalizado) · `ARCHIVED`
  (Archivado). Approving the budget moves `DRAFT → ACTIVE` automatically. The form lets the Admin set any status at
  any time (no transition rules in `Project::changeStatus`). The currency is fixed after creation
  (`currency_locked`).
- Access: only Admins create/edit (`POST`/`PATCH /api/projects`). Non-members get 404 "Este proyecto no existe o
  no participas en él." on `/projects/:id`.

## Side effects

- Create/update are written to the audit log (record "Proyecto").

## Unclear

- Should the Admin be able to set "Activo" by hand before the budget is approved, or move an approved project back
  to "Borrador"? The spec (§3.1, §10) only describes approval moving DRAFT → ACTIVE.
- Spec §6 says the portfolio shows "funded" per project; the cards show budget, spent, progress, CPI, SPI and
  alerts, but no funded/deposited amount. Spec §13 (later) matches the cards.

## Sources

- Route: `backend/assets/react/app/router.tsx`
- Components: `pages/dashboard/ui/DashboardPage.tsx`, `widgets/portfolio/ui/Portfolio.tsx`,
  `entities/dashboard/ui/health.tsx`, `pages/projects/ui/ProjectsPage.tsx`, `widgets/project-list/ui/ProjectList.tsx`,
  `features/project-edit/ui/ProjectFormModal.tsx`, `entities/project/` (all under `backend/assets/react/`).
- API: `GET /api/dashboard` → `backend/src/Reporting/UI/Http/Controller/DashboardController.php`;
  `GET|POST /api/projects`, `GET|PATCH /api/projects/{id}` → `backend/src/Project/UI/Http/Controller/ProjectController.php`;
  rules in `backend/src/Project/Domain/Model/Project.php`, inputs `backend/src/Project/UI/Http/Input/`.
  Default currency from `GET /api/settings`.
