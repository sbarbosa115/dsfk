# UI regression run — 2026-09-28 (rebuild-finance)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), with section 7 (Fondos) added in this phase; FIN-02's
  expected "Financiado" corrected to 104,4 % during the run (104,35 % shown with one decimal).
- **Branch:** `feature/rebuild-finance`, this checkout's stack, app served by Symfony on :18091.
- **Data:** reset before the run (drop, create, migrate, `app:create-admin --generate-password`). The data sections
  1–6 create (Laura and Carlos, Torre Norte with Laura as PM and Carlos as Team Lead, the PLN-02 plan with the Acero
  at $ 32.000, submitted and approved, Cimentación started with Excavación met) was built **through the API** with
  a script, not through the screens.
- **How:** Chrome through the real UI (clicks, typing, selects; file uploads built in the page with `DataTransfer`),
  results read from the page; the database and the Apache log where a screen could not prove it.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 40 |
| Pass | 9 (FIN-01 – 08 and PLN-08's Team Lead view as part of FIN-01) |
| Fail | 0 |
| Not run | 31 (AUTH, USR, IMP, SET, PRJ, PLN-01 – 07: their data was created through the API) |

## Results

| IDs | Result | Notes |
|---|---|---|
| AUTH-01 – 05, USR-01 – 08, IMP-01 – 02, SET-01 – 02, PRJ-01 – 07 | Not run | No code of these screens changed in this phase; last passed in the planning run |
| PLN-01 – 07 | Not run as cases | Built through the API. Planning's code changed here only in stage completion (run in FIN-07) and category deletion (run in FIN-08); Vaciado de zapatas was marked met through the screen |
| FIN-01 | Pass | Laura: figures, no Registrar depósito / Usar contingencia / Finalizar etapa. Carlos: tabs "Presupuesto y plan · Resumen", `?tab=finance` shows the plan |
| FIN-02 | Pass after fix | Totals $ 2.000.000 of $ 5.137.506,25, caja menor $ 300.000, contingency $ 200.000; Cimentación $ 1.500.000 with "$ 62.493,75 por encima del presupuesto", 104,4 %; the movement lists the three destinations. Findings 1 and 2 |
| FIN-03 | Pass | empty Monto 1 refused under the field; a 250.000 draw refused under Monto |
| FIN-04 | Pass | contingency $ 50.000 ("usada: $ 150.000"), Estructura $ 150.000, "Uso de contingencia" with the reason |
| FIN-05 | Pass | a text file named `.pdf` refused ("Formato no permitido…"), nothing stored (checked in the database and `var/uploads`); the real PDF shows "Comprobante", served inline as `application/pdf`; Laura opens it and has no Adjuntar or Anular |
| FIN-06 | Pass | TRX-001's void refused ("parte de ese dinero ya se usó…"); the dialog named the deposit; DUP-1 voided: grey row, "Anulado por Administrador el 28 de sept de 2026: “Duplicado”", no buttons; Cimentación back to $ 1.500.000; `dup` finds only it |
| FIN-07 | Pass | the dialog said "$ 1.500.000 pasará a la etapa “Estructura”"; Cimentación green, $ 0, "pasaron a la siguiente"; Estructura $ 1.650.000; "Saldo trasladado" with no buttons; the plan says COMPLETED; the deposit dialog offers only Estructura |
| FIN-08 | Pass | deposit earmarked for Herramienta; Laura's Quitar refused with "La categoría se usa en partidas o gastos…" |
| Dark theme | Pass | Fondos read in Oscuro |

## Findings

1. **A refused amount kept its message after it was corrected** (FIN-03/02): the deposit dialog only cleared
   errors on the next submit. Fixed: editing an allocation resets the errors (`useFinanceAction().reset`); Vitest
   `FinanceBoard.test.tsx` checks the message goes.
2. **Allocation rows without a category were laid out differently** (FIN-02): the "Quitar" button stretched into
   the category column. Fixed: an empty cell keeps the columns.
3. **The stage table scrolled sideways below ~1170 px** (seen at 1087 px): "Finalizar etapa" was cut off. Fixed:
   what was spent moved under the budget ("Gastado: $ 0 (0%)"), notes in money cells wrap, a narrower bar; the
   table now fits at 1087 px (measured).
4. **Wording:** the empty movements list said deposits come "cuando el presupuesto está aprobado" on an approved
   budget. Now "Aún no hay movimientos de dinero."; the tab's own alert covers the unapproved case.
5. The stat tiles' card had extra space below it: the grid's margin is dropped inside a card.

## Conditions

- Sign-in went through `POST /api/login` from the page: the password manager extension blocks the login form.
  Users were switched with "Ver como" (FIN-05, 07, 08) or by signing in again (FIN-01).
- Once, a category added with simulated typing and a click stayed "Procesando…" for 27 s although the server
  answered at once (Apache log); the same form driven from the page answered in 204 ms, and two direct calls in
  130 ms. Taken as the extension holding the submit; two test categories this created were deleted.
- The console showed only the extension's "reading 'global'" error.
