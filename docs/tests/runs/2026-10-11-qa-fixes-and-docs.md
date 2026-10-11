# UI regression run — 2026-10-11 (qa-fixes-and-docs)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), at `3c16a10`.
- **Branch:** `feature/qa-fixes-and-docs` at `3c16a10`, on this checkout's stack (app http://localhost:18091).
- **Data:** the demo seed (`make seed`), reset before the smoke suite.
- **How:** the smoke suite first (Playwright against this stack), until it is green; then the cases left for a person, in a browser driven through the real UI; emails read in the mail catcher; database/logs where a screen could not prove it.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 78 |
| Run by the smoke suite | 8 |
| Left for the manual run | 70 |
| Manual: pass | 1 (UI-11's by-hand part); the rest not run: outside the change |
| Manual: fail | 0 |

## Smoke suite

`python3 ~/.claude/skills/symfony-react-app/scripts/smoke.py` runs it and adds a row here. A run that is not green
is recorded too: write what failed under "Smoke findings", fix it, and run again. The manual run starts only when
the last row is green.

| # | When | Commit | Result | Failed |
|---|---|---|---|---|
| 1 | 2026-10-11 11:07 | `3c16a10` | Green: 14 passed, 0 failed | — |
<!-- smoke.py adds a row per run of the whole suite -->

### Smoke findings

<!-- Numbered: the failing test (its case ID), whether the app or the test was wrong, the cause, and the fix (commit). -->

## Manual run

Replace "Not run" with Pass, Fail, "Pass after fix" (with the commit) or Blocked (with why). Group consecutive
passes into ranges (`AREA-01 – 05`) once done.

| ID | Result | Case / notes |
|---|---|---|
| AUTH-01 | Not run | Wrong credentials are refused without saying which one is wrong. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| AUTH-02 | Not run | Ingresar needs both fields. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| AUTH-03 | Not run | Sign in and out. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| AUTH-04 | Not run | Change your own password. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| AUTH-05 | Not run | Repeated failures lock the sign-in for a while. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-01 | Not run | Create users. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-02 | Not run | An email belongs to one user, whatever its case. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-03 | Not run | Fields are checked one by one. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-04 | Not run | Admins cannot lock themselves out. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-05 | Not run | Grant and remove admin. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-06 | Not run | Disable a user. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-07 | Not run | Only a super admin edits a super admin. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| USR-08 | Not run | Non-admins do not see admin pages. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| IMP-01 | Not run | View the app as another user and come back. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| IMP-02 | Not run | A new user shows up in "Ver como" right away. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| SET-01 | Not run | Invalid values are refused field by field. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| SET-02 | Not run | Save settings. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PRJ-01 | Not run | An admin with no projects is told to create one. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PRJ-02 | Not run | Create a project. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PRJ-03 | Not run | The currency is chosen once. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PRJ-04 | Not run | Build the team: one Project Manager. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PRJ-05 | Not run | Change a role and remove someone. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PRJ-06 | Not run | Search and filter the list. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PRJ-07 | Not run | Members see only their projects, read-only. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-01 | Not run | Categories. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-02 | Not run | Build the plan and let the server do the arithmetic. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-03 | Not run | What blocks sending it is listed. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-04 | Not run | Send it: it is locked for everyone. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-05 | Not run | The Admin returns it with a comment. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-06 | Not run | The Admin approves: the baseline is fixed and the project is active. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-07 | Not run | Progress. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-08 | Not run | Only the Admin reopens a milestone; Team Leads see no money. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-10 | Not run | Send, return and approve an addition. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-11 | Not run | The new stage runs in parallel. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| PLN-12 | Not run | An emptied addition goes away; Team Leads — by hand: Adición 2 and the Team Lead. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-01 | Not run | Who sees the money. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-02 | Not run | A deposit split among a stage, the caja menor and the contingency. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-03 | Not run | Refusals say where. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-04 | Not run | Contingency to a stage that ran short. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-05 | Not run | Proof of deposit. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-06 | Not run | Voiding keeps the record. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-07 | Not run | Completing a stage sends its money where the Admin says. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-08 | Not run | A category holding money stays. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-11 | Not run | The fund tops up the caja menor. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| FIN-12 | Not run | Voiding an assignment gives the fund its money back. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-01 | Not run | A Team Lead records what they paid. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-02 | Not run | Approval needs a receipt. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-03 | Not run | Rejected, corrected, sent again. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-04 | Not run | The PM approves up to the limit. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-05 | Not run | Above the limit an Admin approves too. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-06 | Not run | Paying Team Leads back from the caja menor. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-07 | Not run | The PM pays from the caja menor or a stage. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| EXP-08 | Not run | An Admin voids a mistake; its money goes back. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| CAJ-01 | Not run | What moved in the cycle. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| CAJ-02 | Not run | The PM closes the cycle. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| CAJ-03 | Not run | A closed cycle is final. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| CAJ-04 | Not run | The Admin signs it off. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| DSH-01 | Not run | The portfolio. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| DSH-02 | Not run | A project's Tablero. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| AUD-01 | Not run | Who changed what. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| AUD-02 | Not run | Admins only. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| MAIL-01 | Not run | An expense waiting for the PM. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| MAIL-02 | Not run | A rejection reaches the Team Lead. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| MAIL-03 | Not run | The daily digest. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| UI-01 | Not run | A tab's main action ends its filter bar, filled in its colour — by hand: the colours (indigo for Registrar, green for Reembolsar outlined). Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| UI-05 | Not run | On a phone every table is a list of cards — by hand: Proyectos, Usuarios, Fondos, Caja menor and Auditoría at 390 px (tint and edge kept, "Columna: valor" lines, a thumb-sized main button, the menu as a sheet from the bottom). Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| UI-06 | Not run | On a phone the project tabs show that they scroll — by hand: the fade at the right edge while tabs are hidden. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| UI-08 | Not run | Auditoría speaks Spanish and Bogotá time — by hand: the filter bar on one row with a long project name. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| UI-09 | Not run | Row colours have their key, and two statuses never share a colour. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |
| UI-10 | Not run | Pesos are whole on screen and exact where a decision is made — by hand: the tooltip. Not walked in this run: outside the change (table layout at 601–1279 px, two tile lines, help and docs); PHPUnit 189 and Vitest 141 green |

## Findings

None.

## Conditions

- The by-hand checks were made from screenshots taken through the real UI by a throwaway Playwright script in the stack's `e2e` container (not committed).
