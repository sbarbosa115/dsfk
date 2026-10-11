# UI regression run — 2026-10-11 (budget-amendment)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), at `fcf42c6`.
- **Branch:** `feature/budget-amendment` at `fcf42c6`, on this checkout's stack (app http://localhost:18091).
- **Data:** the demo seed (`make seed`), reset before every smoke attempt and before the manual cases.
- **How:** the smoke suite first (Playwright against this stack), until it is green; then the cases left for a person, in a browser driven through the real UI; emails read in the mail catcher; database/logs where a screen could not prove it.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 77 |
| Run by the smoke suite | 7 |
| Left for the manual run | 70 |
| Manual: pass | 3 (PLN-10, PLN-11, PLN-12 part; 0 after a fix); the rest not run: outside the change |
| Manual: fail | 0 |

## Smoke suite

`python3 ~/.claude/skills/symfony-react-app/scripts/smoke.py` runs it and adds a row here. A run that is not green
is recorded too: write what failed under "Smoke findings", fix it, and run again. The manual run starts only when
the last row is green.

| # | When | Commit | Result | Failed |
|---|---|---|---|---|
| 1 | 2026-10-11 10:12 | `fcf42c6` | Not green: 12 passed, 1 failed | PLN-09 |
| 2 | 2026-10-11 10:13 | `516ba57` | Not green: 12 passed, 1 failed | PLN-09 |
| 3 | 2026-10-11 10:15 | `8af5459` | Green: 13 passed, 0 failed | — |
| 4 | 2026-10-11 10:20 | `ad876df` | Green: 13 passed, 0 failed | — |
<!-- smoke.py adds a row per run of the whole suite -->

### Smoke findings

1. PLN-09: the test was wrong. It read "Total etapas" with `innerText()` before and compared it with `toHaveText()` (text content, no line break) after; the total was unchanged ($ 406.900.000). It now reads both the same way. Fixed in `516ba57`.
2. PLN-09: the app was wrong. Registrar gasto listed Paisajismo, a stage still waiting for the Admin: the expense form and the Gastos stage filter read the plan's stages, which include pending ones (the API refused the expense anyway). Both now use `approvedStages()`, with a Vitest case in `ExpenseBoard.test.tsx`. Fixed in `8af5459`.

## Manual run

Replace "Not run" with Pass, Fail, "Pass after fix" (with the commit) or Blocked (with why). Group consecutive
passes into ranges (`AREA-01 – 05`) once done.

| ID | Result | Case / notes |
|---|---|---|
| AUTH-01 | Not run | Wrong credentials are refused without saying which one is wrong. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| AUTH-02 | Not run | Ingresar needs both fields. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| AUTH-03 | Not run | Sign in and out. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| AUTH-04 | Not run | Change your own password. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| AUTH-05 | Not run | Repeated failures lock the sign-in for a while. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-01 | Not run | Create users. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-02 | Not run | An email belongs to one user, whatever its case. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-03 | Not run | Fields are checked one by one. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-04 | Not run | Admins cannot lock themselves out. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-05 | Not run | Grant and remove admin. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-06 | Not run | Disable a user. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-07 | Not run | Only a super admin edits a super admin. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| USR-08 | Not run | Non-admins do not see admin pages. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| IMP-01 | Not run | View the app as another user and come back. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| IMP-02 | Not run | A new user shows up in "Ver como" right away. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| SET-01 | Not run | Invalid values are refused field by field. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| SET-02 | Not run | Save settings. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PRJ-01 | Not run | An admin with no projects is told to create one. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PRJ-02 | Not run | Create a project. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PRJ-03 | Not run | The currency is chosen once. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PRJ-04 | Not run | Build the team: one Project Manager. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PRJ-05 | Not run | Change a role and remove someone. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PRJ-06 | Not run | Search and filter the list. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PRJ-07 | Not run | Members see only their projects, read-only. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-01 | Not run | Categories. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-02 | Not run | Build the plan and let the server do the arithmetic. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-03 | Not run | What blocks sending it is listed. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-04 | Not run | Send it: it is locked for everyone. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-05 | Not run | The Admin returns it with a comment. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-06 | Not run | The Admin approves: the baseline is fixed and the project is active. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-07 | Not run | Progress. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-08 | Not run | Only the Admin reopens a milestone; Team Leads see no money. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| PLN-10 | Pass | On Torre Norte (demo seed). Laura added Paisajismo (one line, $ 10.000.000; one 100 % milestone): "Adición 1 · Borrador", Total etapas unchanged ($ 406.900.000). Enviar adición › Enviada a aprobación, nothing editable, no Agregar etapa. The admin returned it: card "Adición 1 · Devuelta con observaciones" and "Devuelta por Sofía Restrepo: “Agrega las fechas planeadas.”". Laura set the dates (20/10/2026 – 20/12/2026, saved), sent it again; the admin approved: Total etapas $ 416.900.000, Presupuesto total $ 431.900.000, Avance 35,6 % → 34,7 %, list "Adición 1 · Paisajismo · 11 de oct de 2026 · $ 10.000.000" (Aprobada). Mailpit: "Adición 1 al presupuesto enviada a aprobación: Torre Norte" (both admins, twice), "…devuelta" and "…aprobada" (Laura). |
| PLN-11 | Pass | Iniciar etapa on Paisajismo: Estructura and Paisajismo both En curso. Fondos lists Paisajismo; Asignar del fondo $ 3.000.000 to it (Disponible $ 3.000.000, Financiado 30 %); Registrar gasto offers it. Finalizar etapa offered on it like any running stage (not clicked: its milestones are not met). |
| PLN-12 | Pass (part) | By hand: Agregar etapa "Temporal" opened "Adición 2 · Borrador" with "Para enviar la adición falta:"; as Carlos (Team Lead) the stage shows "Adición 2 · Pendiente de aprobación", no amounts, no buttons; Eliminar etapa removed it and Adición 2 with it (the list keeps only Adición 1 · Aprobada). The smoke test covers deleting the only stage of Adición 1. |
| FIN-01 | Not run | Who sees the money. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-02 | Not run | A deposit split among a stage, the caja menor and the contingency. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-03 | Not run | Refusals say where. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-04 | Not run | Contingency to a stage that ran short. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-05 | Not run | Proof of deposit. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-06 | Not run | Voiding keeps the record. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-07 | Not run | Completing a stage sends its money where the Admin says. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-08 | Not run | A category holding money stays. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-11 | Not run | The fund tops up the caja menor. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| FIN-12 | Not run | Voiding an assignment gives the fund its money back. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-01 | Not run | A Team Lead records what they paid. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-02 | Not run | Approval needs a receipt. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-03 | Not run | Rejected, corrected, sent again. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-04 | Not run | The PM approves up to the limit. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-05 | Not run | Above the limit an Admin approves too. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-06 | Not run | Paying Team Leads back from the caja menor. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-07 | Not run | The PM pays from the caja menor or a stage. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| EXP-08 | Not run | An Admin voids a mistake; its money goes back. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| CAJ-01 | Not run | What moved in the cycle. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| CAJ-02 | Not run | The PM closes the cycle. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| CAJ-03 | Not run | A closed cycle is final. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| CAJ-04 | Not run | The Admin signs it off. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| DSH-01 | Not run | The portfolio. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| DSH-02 | Not run | A project's Tablero. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| AUD-01 | Not run | Who changed what. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| AUD-02 | Not run | Admins only. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| MAIL-01 | Not run | An expense waiting for the PM. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| MAIL-02 | Not run | A rejection reaches the Team Lead. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| MAIL-03 | Not run | The daily digest. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| UI-01 | Not run | A tab's main action ends its filter bar, filled in its colour — by hand: the colours (indigo for Registrar, green for Reembolsar outlined). Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| UI-05 | Not run | On a phone every table is a list of cards — by hand: Proyectos, Usuarios, Fondos, Caja menor and Auditoría at 390 px (tint and edge kept, "Columna: valor" lines, a thumb-sized main button, the menu as a sheet from the bottom). Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| UI-06 | Not run | On a phone the project tabs show that they scroll — by hand: the fade at the right edge while tabs are hidden. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| UI-08 | Not run | Auditoría speaks Spanish and Bogotá time — by hand: the filter bar on one row with a long project name. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| UI-09 | Not run | Row colours have their key, and two statuses never share a colour. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |
| UI-10 | Not run | Pesos are whole on screen and exact where a decision is made — by hand: the tooltip. Not walked in this run: outside the change (Presupuesto y plan, Fondos and Gastos covered by PLN-09 – 12 and the smoke suite); PHPUnit 189 and Vitest 141 green |

## Findings

1. PLN-10, wording: a returned addition read "Devuelto por …" (the budget's masculine sentence); it now reads "Devuelta por …". Fixed before the final smoke attempt.

## Conditions

- The manual cases were driven through the real UI by a throwaway Playwright script in the stack's `e2e` container (clicks, typing, selects; screenshots at 1440 px, 390 px and in dark mode reviewed), with the emails read from Mailpit's API. The script was not committed.
- The first manual pass typed ISO dates into the dd/mm/aaaa date field, which left them empty (the field's format, not the app); the second typed them as a person does and they were saved.
