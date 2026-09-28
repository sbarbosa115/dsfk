# UI regression run — 2026-09-28 (rebuild-expenses)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), with sections 8 (Gastos) and 9 (Caja menor) added in this
  phase.
- **Branch:** `feature/rebuild-expenses`, this checkout's stack, app served by Symfony on :18091.
- **Data:** continued from the finance run (not reset): the state FIN-08 left, as sections 8 and 9 expect.
- **How:** Chrome through the real UI (users switched with "Ver como"; fields set from the page with native
  input events, see Conditions; receipts built in the page with `DataTransfer`), results read from the page and
  the API.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 52 |
| Pass | 12 (EXP-01 – 08, CAJ-01 – 04) |
| Fail | 0 |
| Not run | 40 (sections 1–7: passed in the planning and finance runs; nothing of theirs changed here except the finance summary's "Gastado", checked in EXP-04) |

## Results

| IDs | Result | Notes |
|---|---|---|
| AUTH … FIN | Not run | see Summary |
| EXP-01 | Pass | no "Se paga desde" for Carlos; blue "Pendiente", "Pagado por Carlos Pérez", "Sin factura o recibo"; tabs Presupuesto y plan · Gastos · Resumen |
| EXP-02 | Pass | "Adjunta la factura o el recibo antes de aprobar el gasto."; the receipt link opens (200) |
| EXP-03 | Pass | red row with the reason; corrected to $ 110.000 and back to Pendiente; the history lists Registrado, Rechazado with the reason, Corregido with "Antes: Cemento gris, $ 120.000, …" (corrected twice: the first time the simulated typing did not reach the field, see Conditions) |
| EXP-04 | Pass | Aprobado; Por reembolsar 1 · $ 110.000; finance: spent $ 110.000, Estructura 3,44 % |
| EXP-05 | Pass | the dialog warned "Supera el límite de $ 500.000…"; "Espera al administrador" with no Aprobar for Laura; the super admin approved |
| EXP-06 | Pass | "Reembolsar $ 710.000" refused with "Fondos insuficientes. Disponible: $ 300.000."; Cemento gris alone: teal Reembolsado, "28 de sept de 2026 · Transferencia"; caja menor $ 190.000 |
| EXP-07 | Pass | Clavos and Arena approved at once; caja menor $ 145.000; Estructura $ 1.551.000; $ 5.000.000 refused with "Disponible: $ 1.551.000." under Monto |
| EXP-08 | Pass | Arena Anulado, Estructura back to $ 1.651.000; no Anular on Reembolsado/Anulado rows; Carlos's list has his two expenses and he gets 403 on the caja menor |
| CAJ-01 | Pass after fix | saldo $ 145.000; Depósito +$ 300.000 with its Comprobante, Reembolso −$ 110.000 "Reembolso: Carlos Pérez – Cemento gris", Gasto −$ 45.000 "Clavos". Finding 1 |
| CAJ-02 | Pass | "…saldo de $ 145.000…"; #1 amber "Cerrado, por firmar", $ 145.000; Ciclos por firmar 1; Ciclo 2 empty; no Firmar for Laura |
| CAJ-03 | Pass | "El movimiento pertenece a un ciclo de caja menor cerrado y ya no se modifica." Finding 2 |
| CAJ-04 | Pass | green Firmado; the cycle view lists its movements, "Fin de mes", who closed and who signed |
| Dark theme | Pass | Gastos and Caja menor read in Oscuro |
| Concurrency (audit finding 1) | Pass | on a scratch project ("Prueba concurrencia"), two reimbursements of the same expense sent at the same instant from two sessions: one 201, one 422; the caja menor paid out once |

## Findings

1. **A deposit in the caja menor did not say which one it was** (CAJ-01): only the movement's note was shown and
   most deposits have none. Fixed: the cycle's movements carry the payment method and reference ("Efectivo · R-1 ·
   Fondo inicial"); API and Vitest updated.
2. **Anular stays offered on an expense of a closed cycle** (CAJ-03); the server refuses it with a clear message.
   Left as is: an expense does not know its cycle's state, and the refusal explains it (noted in the README).

## Conditions

- Keystrokes from the automation stopped reaching the page midway (focus); amounts and text were then set from
  the page with the native value setter and an `input` event, which React handles as typing. Selects the same way
  with `change`.
- The console showed only the extension's "reading 'global'" error.
- The scratch project "Prueba concurrencia" stays in the dev database.
