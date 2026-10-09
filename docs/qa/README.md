# QA

Written by the `qa-expert` skill. Each run uses the app view by view, looking for UI/UX inconsistencies and
functional issues, and files what it finds.

- `flows/`: how the app works, one doc per area: views, actions, rules, expected behaviour.
- `issues/README.md`: **the priority list** of every issue (generated; do not edit by hand).
- `issues/QA-nnnn-*.md`: one issue per file.
- `issues/runs/`: one report per run: what was covered, found, re-checked.

Priorities: **P0** data loss, security or privacy leak, money wrong, a core flow nobody can complete · **P1** a main
flow broken for some, wrong results, a crash, an unusable screen · **P2** a secondary flow broken, a clear departure
from the design · **P3** polish and rare edge cases.

## Design sources

The main design every screen is measured against:

- `~/Development/mdx/CLAUDE.md` § Tables (house style: actions, button colours, row tints) and
  `~/Development/mdx/.claude/skills/new-feature/references/ux.md` (page, tabs, words, cells, empty states): the UI kit
  dsfk was copied from.
- `backend/assets/styles/app.css`: the tokens (light and dark).
- `backend/assets/react/shared/ui/ui.tsx`: the component kit (`ActionButton`, `DataTable`, `ListView`, `Row`,
  `RowLegend`, `FilterBar`, `FormModal`) and `ConfirmModal.tsx`.

Reference screens (the ones that follow it best):

- Proyectos (`/projects`): filter bar with the main action at its end, row legend, one view action per row.
- Presupuesto y plan › Categorías: inline add form, worded "Quitar" and the blue edit pencil.

## Coverage

| View | Route | Role | Flow doc | Last checked | Open issues |
|---|---|---|---|---|---|
| Login | `/login` | — | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-08 | — (all fixed) |
| App shell, drawer, Ver como, Tema | `(all)` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-08 | — (all fixed) |
| Cambiar contraseña (modal) | `(sidebar)` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-08 | — (all fixed) |
| Tablero (portfolio) | `/dashboard` | Admin | [portfolio](flows/portfolio.md) | 2026-10-08 | — (all fixed) |
| Proyectos + Nuevo/Editar proyecto | `/projects` | Admin | [portfolio](flows/portfolio.md) | 2026-10-08 | — (all fixed) |
| Presupuesto y plan | `/projects/:id?tab=plan` | Admin | [project](flows/project.md) | 2026-10-08 | — (all fixed) |
| Tablero (project) | `/projects/:id?tab=dashboard` | Admin | [project](flows/project.md) | 2026-10-08 | — (all fixed) |
| Gastos + modals | `/projects/:id?tab=expenses` | Admin | [project](flows/project.md) | 2026-10-08 | — (all fixed) |
| Fondos + modals | `/projects/:id?tab=finance` | Admin | [project](flows/project.md) | 2026-10-08 | — (all fixed) |
| Caja menor + modals | `/projects/:id?tab=petty-cash` | Admin | [project](flows/project.md) | 2026-10-08 | — (all fixed) |
| Resumen (datos, equipo) | `/projects/:id?tab=overview` | Admin | [project](flows/project.md) | 2026-10-08 | — (all fixed) |
| Usuarios + modals | `/users` | Admin | [admin](flows/admin.md) | 2026-10-08 | — (all fixed) |
| Configuración | `/settings` | Admin | [admin](flows/admin.md) | 2026-10-08 | — (all fixed) |
| Auditoría | `/audit` | Admin | [admin](flows/admin.md) | 2026-10-08 | — (all fixed) |
| Documentación | `/help` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-08 | — (all fixed) |
| Página no encontrada | `/*` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-08 | — (all fixed) |
