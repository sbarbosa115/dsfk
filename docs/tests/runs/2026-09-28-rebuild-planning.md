# UI regression run — 2026-09-28 (rebuild-planning)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), at `5d9c367` (PLN-07's expected share updated during the run).
- **Branch:** `feature/rebuild-planning` at `5d9c367`, this checkout's stack, app served by Symfony on :18091.
- **Data:** reset before the run (drop, create, migrate, `app:create-admin --generate-password`).
- **How:** Chrome through the real UI: values set with native setters and `input`/`change` events, buttons clicked,
  results read from the page. Each group of cases ran as one script that saved what it saw in `localStorage`, read
  back after a reload (see Conditions).

## Summary

| | Cases |
|---|---|
| Cases in the suite | 32 |
| Pass | 32 |
| Fail | 0 |

## Results

| IDs | Result | Notes |
|---|---|---|
| AUTH-01 – 05 | Pass | as in the previous run; AUTH-04 as Laura (then signed in with the new password in USR-08), AUTH-05 with `ana@demo.test` |
| USR-01 – 08 | Pass | USR-06's sign-in message checked after disabling Carlos again |
| IMP-01 – 02 | Pass | |
| SET-01 – 02 | Pass | |
| PRJ-01 – 07 | Pass | PRJ-05's role swap order not repeated (the refusal, the removal and adding back were) |
| PLN-01 – 02 | Pass | duplicate category refused; line hint "$ 437.506,25"; totals $ 4.437.506,25 / $ 500.000 / $ 4.937.506,25; shares 32,4 % / 67,6 %; unit price $ 35.000,50 |
| PLN-03 | Pass | the list said "… deben sumar 100 % (hoy suman 40%)" with Enviar disabled; 60,01 % refused under Peso |
| PLN-04 – 06 | Pass | locked while sent (no add buttons, no contingency pencil, no review buttons for the PM); returned with the comment shown; line edited and sent again; approved: note, project ACTIVE, Iniciar/Marcar buttons, no stage delete, dates disabled with the hint |
| PLN-07 | Pass | stage "En curso · Iniciada el …", Excavación green with date, Laura and the note, stage 40 %, Avance 12,4 %. The red "Atrasado" row was not seen here: no milestone of this data is overdue yet (the rule is covered by `PlanApiTest::testAMilestoneWhosePlannedDatePassedIsOverdue`) |
| PLN-08 | Pass | no Reabrir for the PM; the super admin's Reabrir took the stage to 0 %; Carlos sees no amounts, no Partidas, no buttons |

## Findings

1. **A refused category name kept its message after the user typed again** (PLN-01): the form kept the last
   errors until the next submit. Fixed: `useSubmit().reset()` on typing; Vitest `CategoriesPanel.test.tsx`.
2. **Unit prices with centavos were rounded on screen** (found while building the phase, before the run):
   `$ 35.000,50` showed as `$ 35.001`. Fixed in `formatMoney` (centavos when there are some), with a test.

## Conditions

- The password manager extension interrupts scripts on pages with password fields; every group was therefore run
  as one script that stored its observations, read after a reload. One group failed to start (extension
  disconnect) and was run again; no case depends on a partial run.
- Screens run on the Symfony-served build (Encore watch in the node service).
