# UI regression run — 2026-10-11 (project-fund)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), at `bf4f924`.
- **Branch:** `feature/project-fund` at `bf4f924`, on this checkout's stack (app <!-- URL -->).
- **Data:** <!-- reset to the seed before the run / not reset (why) -->
- **How:** the smoke suite first (Playwright against this stack), until it is green; then the cases left for a person, in a browser driven through the real UI; emails read in the mail catcher; database/logs where a screen could not prove it.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 73 |
| Run by the smoke suite | 6 |
| Left for the manual run | 67 |
| Manual: pass | <!-- N (M after a fix made during the run) --> |
| Manual: fail | <!-- N: IDs --> |

## Smoke suite

`python3 ~/.claude/skills/symfony-react-app/scripts/smoke.py` runs it and adds a row here. A run that is not green
is recorded too: write what failed under "Smoke findings", fix it, and run again. The manual run starts only when
the last row is green.

| # | When | Commit | Result | Failed |
|---|---|---|---|---|
<!-- smoke.py adds a row per run of the whole suite -->

### Smoke findings

<!-- Numbered: the failing test (its case ID), whether the app or the test was wrong, the cause, and the fix (commit). -->

## Manual run

Replace "Not run" with Pass, Fail, "Pass after fix" (with the commit) or Blocked (with why). Group consecutive
passes into ranges (`AREA-01 – 05`) once done.

| ID | Result | Case / notes |
|---|---|---|
| AUTH-01 | Not run | Wrong credentials are refused without saying which one is wrong |
| AUTH-02 | Not run | Ingresar needs both fields |
| AUTH-03 | Not run | Sign in and out |
| AUTH-04 | Not run | Change your own password |
| AUTH-05 | Not run | Repeated failures lock the sign-in for a while |
| USR-01 | Not run | Create users |
| USR-02 | Not run | An email belongs to one user, whatever its case |
| USR-03 | Not run | Fields are checked one by one |
| USR-04 | Not run | Admins cannot lock themselves out |
| USR-05 | Not run | Grant and remove admin |
| USR-06 | Not run | Disable a user |
| USR-07 | Not run | Only a super admin edits a super admin |
| USR-08 | Not run | Non-admins do not see admin pages |
| IMP-01 | Not run | View the app as another user and come back |
| IMP-02 | Not run | A new user shows up in "Ver como" right away |
| SET-01 | Not run | Invalid values are refused field by field |
| SET-02 | Not run | Save settings |
| PRJ-01 | Not run | An admin with no projects is told to create one |
| PRJ-02 | Not run | Create a project |
| PRJ-03 | Not run | The currency is chosen once |
| PRJ-04 | Not run | Build the team: one Project Manager |
| PRJ-05 | Not run | Change a role and remove someone |
| PRJ-06 | Not run | Search and filter the list |
| PRJ-07 | Not run | Members see only their projects, read-only |
| PLN-01 | Not run | Categories |
| PLN-02 | Not run | Build the plan and let the server do the arithmetic |
| PLN-03 | Not run | What blocks sending it is listed |
| PLN-04 | Not run | Send it: it is locked for everyone |
| PLN-05 | Not run | The Admin returns it with a comment |
| PLN-06 | Not run | The Admin approves: the baseline is fixed and the project is active |
| PLN-07 | Not run | Progress |
| PLN-08 | Not run | Only the Admin reopens a milestone; Team Leads see no money |
| FIN-01 | Not run | Who sees the money |
| FIN-02 | Not run | A deposit split among a stage, the caja menor and the contingency |
| FIN-03 | Not run | Refusals say where |
| FIN-04 | Not run | Contingency to a stage that ran short |
| FIN-05 | Not run | Proof of deposit |
| FIN-06 | Not run | Voiding keeps the record |
| FIN-07 | Not run | Completing a stage sends its money where the Admin says |
| FIN-08 | Not run | A category holding money stays |
| FIN-11 | Not run | The fund tops up the caja menor |
| FIN-12 | Not run | Voiding an assignment gives the fund its money back |
| EXP-01 | Not run | A Team Lead records what they paid |
| EXP-02 | Not run | Approval needs a receipt |
| EXP-03 | Not run | Rejected, corrected, sent again |
| EXP-04 | Not run | The PM approves up to the limit |
| EXP-05 | Not run | Above the limit an Admin approves too |
| EXP-06 | Not run | Paying Team Leads back from the caja menor |
| EXP-07 | Not run | The PM pays from the caja menor or a stage |
| EXP-08 | Not run | An Admin voids a mistake; its money goes back |
| CAJ-01 | Not run | What moved in the cycle |
| CAJ-02 | Not run | The PM closes the cycle |
| CAJ-03 | Not run | A closed cycle is final |
| CAJ-04 | Not run | The Admin signs it off |
| DSH-01 | Not run | The portfolio |
| DSH-02 | Not run | A project's Tablero |
| AUD-01 | Not run | Who changed what |
| AUD-02 | Not run | Admins only |
| MAIL-01 | Not run | An expense waiting for the PM |
| MAIL-02 | Not run | A rejection reaches the Team Lead |
| MAIL-03 | Not run | The daily digest |
| UI-01 | Not run | A tab's main action ends its filter bar, filled in its colour — by hand: the colours (indigo for Registrar, green for Reembolsar outlined) |
| UI-05 | Not run | On a phone every table is a list of cards — by hand: Proyectos, Usuarios, Fondos, Caja menor and Auditoría at 390 px (tint and edge kept, "Columna: valor" lines, a thumb-sized main button, the menu as a sheet from the bottom) |
| UI-06 | Not run | On a phone the project tabs show that they scroll — by hand: the fade at the right edge while tabs are hidden |
| UI-08 | Not run | Auditoría speaks Spanish and Bogotá time — by hand: the filter bar on one row with a long project name |
| UI-09 | Not run | Row colours have their key, and two statuses never share a colour |
| UI-10 | Not run | Pesos are whole on screen and exact where a decision is made — by hand: the tooltip |

## Findings

<!-- Numbered: what happened, which case, the cause, and the fix (commit) or why it was left. -->

## Conditions

<!-- Anything about the environment that could have affected the result. -->
