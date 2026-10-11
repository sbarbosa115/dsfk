# QA run · 2026-10-11 · project-fund

- **Scope:** what changed since the last run (2026-10-08): the project fund on `feature/project-fund` (PR not merged yet). Fondos and every dialog it opens, the Caja menor cycle (fund top-ups), the Fondos tab on a draft project, the help topics. Open issues re-checked: none were open.
- **Commit:** 6ee1ac0 on `feature/project-fund`
- **Stack:** the worktree's own Docker stack (`../dsfk-project-fund`, app :18091), reset with `make seed` before the run.
- **Accounts:** Admin `admin@demo.test` · PM `pm@demo.test` (Laura, Torre Norte) · demo seed passwords.
- **Widths / themes:** 1440, 1280, 1024, 900, 768, 390 · light and dark (Tema › Oscuro).
- **Reference screens:** Fondos › "Usar contingencia" (the sibling of "Asignar del fondo"); the Contingencia tile (the sibling of the fund tile); Proyectos for the table house style.
- **How:** Claude in Chrome's screenshots kept timing out in this session (and an earlier session there lost its sign-in to other apps' localhost cookies), so the views were driven through the real UI by throwaway Playwright scripts in the stack's `e2e` container (clicks, typing, selects, double-clicks, theme and width changes), with screenshots reviewed and DOM measurements logged; the API was probed with the app's own CSRF header. The scripts were deleted after the run.

## Covered

| View | Role(s) | UI | Functional | Notes |
|---|---|---|---|---|
| Fondos (tiles, Fondos por etapa, Por categoría, Movimientos) | Admin, PM | ✓ all widths, dark | ✓ | PM: figures, no actions; Team Lead not re-checked (unchanged gate) |
| Asignar del fondo (dialog) | Admin | ✓ 1440, 390, dark | ✓ | empty, 0, future date (API), over the cap, note with HTML (shown as text), double-click (one movement), 2001-char note, no destination, draft project (409), PM (403) |
| Registrar depósito (Fondo del proyecto option) | Admin | ✓ | ✓ | |
| Finalizar etapa (Destino del saldo) | Admin | ✓ | ✓ | default fund, other open stages only, milestones pending refused; a non-integer `toStageId` is 422 |
| Anular on an assignment | Admin | ✓ | ✓ | menu holds only Anular, as for draws |
| Caja menor (cycle) | Admin | ✓ | ✓ | the assignment is listed and counted in Entradas |
| Fondos on a draft project | Admin | ✓ | ✓ | no Asignar del fondo, tile at $ 0, alert explains |
| Documentación | Admin | — | ✓ | the topics mention the fund |

## Not covered

- Every other view: unchanged since the last run.
- Team Lead access to Fondos: unchanged code path, not re-walked.
- Tables on other tabs at 601–1200 px (QA-0018 was measured on Fondos only).
- Emails (the low-balance alert after a fund top-up): covered by PHPUnit, not read in Mailpit this run.

## New issues

| Id | Priority | Category | View | Title |
|---|---|---|---|---|
| QA-0018 | P2 | ui | Fondos | Between 601 and about 1200 px the stage table is wider than its card and Finalizar etapa is cut off |
| QA-0019 | P3 | ui | Fondos | The Fondo del proyecto tile's detail line breaks the label: value pattern of its sibling tiles |
| QA-0020 | P3 | functional | Fondos | The Caja menor tile says Depositado and leaves out fund assignments, while the Caja menor tab counts them as Entradas |

Observed and not filed:
- At 1280 px the fifth tile wraps onto its own row: seen and accepted by the user on 2026-10-11 (left as the shared `.stat-grid` lays it out).
- Typing "-5" in a money field shows "5" (the field drops the sign) and saves $ 5: the shared money field, visible before saving.
- An amount with three decimals sent straight to the API says "Debe ser un monto positivo." (shared `Patterns::AMOUNT` message); the dialog never sends one.
- `PETTY_CASH` with a `stageId` is accepted and the stage ignored, as deposits do.

## Seen again (still open)

- none (no issue was open)

## Found fixed

- QA-0001 still fixed at 390 px on Fondos (tables are cards).

## Regressions (fixed before, back now)

- none

## Unclear: questions for the user

- "Asignar del fondo" uses the *setup* colour (indigo, like "Registrar depósito"), while "Usar contingencia", the other action that moves money already in the project, uses *revert* (amber). Is moving fund money an "add" (setup) or should it match the contingency draw?
