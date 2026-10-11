# UI regression run — 2026-10-11 (project-fund)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), at `bf4f924`.
- **Branch:** `feature/project-fund` at `bf4f924`, on this checkout's stack (app http://localhost:18091).
- **Data:** the demo seed (`make seed`), reset before the smoke suite and again before the manual cases.
- **How:** the smoke suite first (Playwright against this stack), until it is green; then the cases left for a person, in a browser driven through the real UI; emails read in the mail catcher; database/logs where a screen could not prove it.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 73 |
| Run by the smoke suite | 6 |
| Left for the manual run | 67 |
| Manual: pass | 3 (FIN-07, FIN-11, FIN-12); the other 64 not run: outside the change |
| Manual: fail | 0 |

## Smoke suite

`python3 ~/.claude/skills/symfony-react-app/scripts/smoke.py` runs it and adds a row here. A run that is not green
is recorded too: write what failed under "Smoke findings", fix it, and run again. The manual run starts only when
the last row is green.

| # | When | Commit | Result | Failed |
|---|---|---|---|---|
| 1 | 2026-10-11 08:39 | `02720a9` | Not green: 10 passed, 1 failed | FIN-10 |
| 2 | 2026-10-11 08:40 | `df37751` | Green: 11 passed, 0 failed | — |
| 3 | 2026-10-11 08:49 | `2aa9398` | Green: 11 passed, 0 failed | — |
<!-- smoke.py adds a row per run of the whole suite -->

### Smoke findings

1. FIN-10: the test was wrong. `getByRole('row', {name: /Acabados/})` also matched the new "Asignación del fondo" movement, which names Acabados (strict mode). It now finds the stage's row by its status (`/^Pendiente: Acabados/`). Fixed in `df37751`.

## Manual run

Replace "Not run" with Pass, Fail, "Pass after fix" (with the commit) or Blocked (with why). Group consecutive
passes into ranges (`AREA-01 – 05`) once done.

| ID | Result | Case / notes |
|---|---|---|
| AUTH-01 | Not run | Wrong credentials are refused without saying which one is wrong. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| AUTH-02 | Not run | Ingresar needs both fields. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| AUTH-03 | Not run | Sign in and out. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| AUTH-04 | Not run | Change your own password. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| AUTH-05 | Not run | Repeated failures lock the sign-in for a while. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-01 | Not run | Create users. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-02 | Not run | An email belongs to one user, whatever its case. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-03 | Not run | Fields are checked one by one. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-04 | Not run | Admins cannot lock themselves out. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-05 | Not run | Grant and remove admin. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-06 | Not run | Disable a user. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-07 | Not run | Only a super admin edits a super admin. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| USR-08 | Not run | Non-admins do not see admin pages. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| IMP-01 | Not run | View the app as another user and come back. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| IMP-02 | Not run | A new user shows up in "Ver como" right away. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| SET-01 | Not run | Invalid values are refused field by field. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| SET-02 | Not run | Save settings. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PRJ-01 | Not run | An admin with no projects is told to create one. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PRJ-02 | Not run | Create a project. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PRJ-03 | Not run | The currency is chosen once. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PRJ-04 | Not run | Build the team: one Project Manager. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PRJ-05 | Not run | Change a role and remove someone. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PRJ-06 | Not run | Search and filter the list. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PRJ-07 | Not run | Members see only their projects, read-only. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-01 | Not run | Categories. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-02 | Not run | Build the plan and let the server do the arithmetic. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-03 | Not run | What blocks sending it is listed. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-04 | Not run | Send it: it is locked for everyone. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-05 | Not run | The Admin returns it with a comment. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-06 | Not run | The Admin approves: the baseline is fixed and the project is active. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-07 | Not run | Progress. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| PLN-08 | Not run | Only the Admin reopens a milestone; Team Leads see no money. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-01 | Not run | Who sees the money. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-02 | Not run | A deposit split among a stage, the caja menor and the contingency. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-03 | Not run | Refusals say where. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-04 | Not run | Contingency to a stage that ran short. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-05 | Not run | Proof of deposit. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-06 | Not run | Voiding keeps the record. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-07 | Pass | On the demo seed (Torre Norte), adapted: as the PM, Iniciar etapa on Acabados and the last two Estructura milestones met, so two stages were in progress. Finalizar etapa on Estructura: Destino del saldo defaulted to Fondo del proyecto, offered only "Etapa: Acabados" (not Estructura itself), and the sentence followed the choice. Confirmed with the default: Estructura Finalizada, "$ 47.200.000 trasladados al finalizar", the fund went from $ 15.000.000 to $ 62.200.000, a "Saldo trasladado" row with no actions. |
| FIN-08 | Not run | A category holding money stays. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| FIN-11 | Pass | Asignar del fondo › Caja menor $ 100.000: the Caja menor tile grew to $ 3.830.000; Caja menor › Ciclo 2 lists the "Asignación del fondo" and Entradas counts it ($ 2.100.000). Over the cap (999.999.999): "El monto supera el saldo disponible del fondo del proyecto." under Monto. |
| FIN-12 | Pass | Asignar del fondo › Acabados $ 2.000.000, then Más › Anular › "Etapa equivocada": the row is grey with the reason, the fund is back to $ 62.100.000 and Acabados to $ 0. |
| EXP-01 | Not run | A Team Lead records what they paid. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| EXP-02 | Not run | Approval needs a receipt. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| EXP-03 | Not run | Rejected, corrected, sent again. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| EXP-04 | Not run | The PM approves up to the limit. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| EXP-05 | Not run | Above the limit an Admin approves too. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| EXP-06 | Not run | Paying Team Leads back from the caja menor. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| EXP-07 | Not run | The PM pays from the caja menor or a stage. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| EXP-08 | Not run | An Admin voids a mistake; its money goes back. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| CAJ-01 | Not run | What moved in the cycle. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| CAJ-02 | Not run | The PM closes the cycle. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| CAJ-03 | Not run | A closed cycle is final. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| CAJ-04 | Not run | The Admin signs it off. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| DSH-01 | Not run | The portfolio. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| DSH-02 | Not run | A project's Tablero. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| AUD-01 | Not run | Who changed what. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| AUD-02 | Not run | Admins only. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| MAIL-01 | Not run | An expense waiting for the PM. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| MAIL-02 | Not run | A rejection reaches the Team Lead. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| MAIL-03 | Not run | The daily digest. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| UI-01 | Not run | A tab's main action ends its filter bar, filled in its colour — by hand: the colours (indigo for Registrar, green for Reembolsar outlined). Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| UI-05 | Not run | On a phone every table is a list of cards — by hand: Proyectos, Usuarios, Fondos, Caja menor and Auditoría at 390 px (tint and edge kept, "Columna: valor" lines, a thumb-sized main button, the menu as a sheet from the bottom). Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| UI-06 | Not run | On a phone the project tabs show that they scroll — by hand: the fade at the right edge while tabs are hidden. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| UI-08 | Not run | Auditoría speaks Spanish and Bogotá time — by hand: the filter bar on one row with a long project name. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| UI-09 | Not run | Row colours have their key, and two statuses never share a colour. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |
| UI-10 | Not run | Pesos are whole on screen and exact where a decision is made — by hand: the tooltip. Not walked in this run: outside the change (Fondos and Caja menor only); PHPUnit 178 and Vitest 139 green |

## Findings

1. Layout, not a failure: at 1280 px wide the fifth tile (Fondo del proyecto) wraps onto a second row of the stats card; at 1440 px all five fit on one row, and on a phone they are two columns. The grid is the shared `.stat-grid` (auto-fit, 200 px minimum); left as is and raised with the user.

## Conditions

- The manual cases were driven through the real UI by a throwaway Playwright script in this stack's `e2e` container (clicks, selects, typing; screenshots reviewed after each step), not by a person in Chrome: the Chrome session kept being signed out because other local apps share `localhost` cookies. The script was not committed.
- FIN-07 was run on the demo seed, adapted as described, instead of the suite's manual data chain.
- Dark theme (Tema › Oscuro) and the phone (390 px) were checked on Fondos and the Asignar del fondo dialog.
