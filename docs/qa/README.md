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
| Login | `/login` | — | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-07 | — |
| App shell, drawer, Ver como, Tema | `(all)` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-07 | QA-0002 |
| Cambiar contraseña (modal) | `(sidebar)` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-07 | — |
| Tablero (portfolio) | `/dashboard` | Admin | [portfolio](flows/portfolio.md) | 2026-10-07 | QA-0015, QA-0013 |
| Proyectos + Nuevo/Editar proyecto | `/projects` | Admin | [portfolio](flows/portfolio.md) | 2026-10-07 | QA-0001, QA-0002, QA-0011 |
| Presupuesto y plan | `/projects/:id?tab=plan` | Admin | [project](flows/project.md) | 2026-10-07 | QA-0002, QA-0003, QA-0005, QA-0009, QA-0014 |
| Tablero (project) | `/projects/:id?tab=dashboard` | Admin | [project](flows/project.md) | 2026-10-07 | QA-0006, QA-0009, QA-0015 |
| Gastos + modals | `/projects/:id?tab=expenses` | Admin | [project](flows/project.md) | 2026-10-07 | QA-0001, QA-0003, QA-0004, QA-0005, QA-0007, QA-0012 |
| Fondos + modals | `/projects/:id?tab=finance` | Admin | [project](flows/project.md) | 2026-10-07 | QA-0001, QA-0003, QA-0004, QA-0006, QA-0007, QA-0009 |
| Caja menor + modals | `/projects/:id?tab=petty-cash` | Admin | [project](flows/project.md) | 2026-10-07 | QA-0001, QA-0003, QA-0006, QA-0009 |
| Resumen (datos, equipo) | `/projects/:id?tab=overview` | Admin | [project](flows/project.md) | 2026-10-07 | QA-0001, QA-0011, QA-0016 |
| Usuarios + modals | `/users` | Admin | [admin](flows/admin.md) | 2026-10-07 | QA-0001, QA-0002, QA-0017 |
| Configuración | `/settings` | Admin | [admin](flows/admin.md) | 2026-10-07 | QA-0002, QA-0017 |
| Auditoría | `/audit` | Admin | [admin](flows/admin.md) | 2026-10-07 | QA-0001, QA-0008 |
| Documentación | `/help` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-07 | QA-0017 |
| Página no encontrada | `/*` | Admin | [shell-and-auth](flows/shell-and-auth.md) | 2026-10-07 | QA-0002, QA-0006 |
