# UI consistency fixes from the admin audit (QA-0001 – QA-0017)

The admin audit of 2026-10-07 (`docs/qa/`, run `docs/qa/issues/runs/2026-10-07-admin-ui.md`) found 17 UI issues; the
before/after proposal was approved with "go". Decisions taken by default (the proposal assumed them): port MDX's
`RowActions`; COP shows whole pesos with the exact amount in the tooltip; audit times in America/Bogota.

## Who and what

Admins (and the PM, who shares every project tab) on desktop and on a phone: every table, button, modal and empty
state looks and behaves the same on every screen, and the phone is usable (row actions reachable, tabs
discoverable). No API change.

## Where it lives

Frontend only: the kit in `shared/ui` and the widgets, features and pages that use it. Audit field names are
translated in the UI (`audit.field.*` in `shared/i18n/es.ts`), because `AuditEntryOutput.changes` is keyed by the
stored Doctrine field name; a PHPUnit guard asserts every audited field has its Spanish line.

## Build order (one branch, test-first per commit)

1. **Buttons and modal footers (QA-0002, QA-0003).** `ActionButton main` (filled in the action's colour),
   `Button` only `ghost`/`link`, `SubmitButton`, `FormModal action`, `ConfirmModal` main; every caller migrated.
   Colour map: add = setup, edit = edit, save/approve/sign = confirm, reject/void/remove = danger,
   Usar contingencia = revert. Guard: `shared/ui/ButtonGroups.test.ts` (port of MDX).
2. **RowActions (QA-0004).** Port of MDX `RowActions` (no in-menu confirm/upload: dsfk's modals ask), one
   main-action function per table in the widget's `model/rowActions.ts`, adopted in Gastos, Movimientos,
   Usuarios, Caja menor, the plan tables, Proyectos and Auditoría.
3. **DataTable on a phone, in-table empty, cells (QA-0001, QA-0006, QA-0007).** Card layout ≤600px with
   `data-label`s, `empty` rendered as a row under the header, `nowrap` on identifiers, `.num` money.
4. **Screens (QA-0005, QA-0008, QA-0010).** Tab actions at the end of the filter bar; phone tabs with short labels,
   fade and the active tab scrolled into view; stats two columns on a phone; returned-budget alert above the tiles;
   audit field names and Bogota time; the empty "Dinero por mes" shows a message.
5. **Polish (QA-0009, QA-0011 – QA-0017).** Legends and tones, clamps, whole pesos, stage card, portfolio cards,
   members, admin pages.

Not split into parallel items: every step changes `shared/ui/ui.tsx` and `app.css`, and steps 2, 4 and 5 touch the
same screens.

## Verification

`make test` and `make gate` in Docker after every commit. The first Playwright smoke suite (`backend/e2e/`) is set
up here with the cases UI-01 – UI-10 of `docs/tests/ui-regression.md` § 13; the phone and colour checks are manual.
The audit's sweep is re-run at the end and the QA issues are marked fixed with their commit.

## Known gaps

Tablet width (768px) and the PM/Team Lead views were not audited; `Modal` has no focus trap; charts other than
"Dinero por mes" keep their axes when empty; units ("3 mes") stay as typed; a COP amount's cents are only in the
tooltip; `RowActions` has no in-menu confirmation or upload (the features open their own modals).

## Timeline

Recorded with `timeline.py`, from 2026-10-07 23:04 to 2026-10-08 04:13. Active time leaves out the pauses.

| Step | Started | Active | Wall clock |
|---|---|---|---|
| Plan | 2026-10-07 23:04 | 7m | 7m |
| Branch and stack | 2026-10-07 23:11 | 4m | 4m |
| Build test-first | 2026-10-07 23:15 | 52m | 52m |
| Security audit | 2026-10-08 00:06 | 3m | 3m |
| Regression run (smoke, then manual) | 2026-10-08 00:09 | 15m | 15m |
| Finish (docs, CI) | 2026-10-08 00:24 | 2m | 2m |
| Definition of done | 2026-10-08 00:26 | 3m | 3h 47m |
| Pull request | 2026-10-08 04:13 | 0m | 0m |
| **Total** | | **1h 26m** | **5h 9m** |

Paused for 3h 44m in all.
