# UI regression run — 2026-09-28 (rebuild-ops, full baseline)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), all 12 sections.
- **Branch:** `feature/rebuild-ops`, this checkout's stack (app :18091, Mailpit :18035, the worker consuming the
  queue).
- **Data:** reset first (database dropped, created and migrated, with only the super admin), then built only by
  the suite, in order.
- **How:** Chrome through the real UI ("Ver como" to switch people; fields set from the page with native input
  events; files put on the file inputs from the page), Mailpit's API for the emails, the console command for the
  digest.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 59 |
| Pass | 59 (4 of them after a fix or a wording update, below) |
| Fail | 0 |
| Not run | 0 |

## Results

| IDs | Result | Notes |
|---|---|---|
| AUTH-01 – 03 | Pass | |
| AUTH-04 | Pass | "La contraseña actual no es correcta." under the field; the change took effect (old password 401, new one 200). The "Contraseña actualizada." notice itself was not captured: the password manager extension blocks the page while its dialog is open |
| AUTH-05 | Pass (API) | five wrong passwords for ana@demo.test, then the right one: `too_many_attempts`, which the login page maps to "Demasiados intentos. Intenta de nuevo en unos minutos." Driven through `/api/login` because the extension blocks scripts on the sign-in page |
| USR-01 – 08 | Pass | USR-05's badge checked through USR-07's badge and the audit trail |
| IMP-01 | Pass | Carlos and Laura "· sin proyectos", no admins; banner and her menu; "Volver a Administrador" |
| IMP-02 | Pass | Ana Ruiz in the dropdown right after creating her |
| SET-01 | Pass | "Revisa los campos marcados.", "entre 1 y 100" and "entre 1 y 200" under the two fields. Typing letters into Límite could not be tried: the tool's keystrokes do not reach the page |
| SET-02 | Pass | "Cambios guardados.", `750.000`, alerts `75, 100`, kept after reload |
| PRJ-01 | Pass | |
| PRJ-02 | Pass, wording updated | the end-before-start message; the project opens on Presupuesto y plan (the tab set grew since the case was written), and Resumen shows Borrador, COP, 1 de oct de 2026 – 30 de jun de 2027, Administrador |
| PRJ-03 | Pass | Moneda disabled; the Estado hint follows the choice; the new description shows |
| PRJ-04 – 05 | Pass | second manager refused with its message; role swaps; "Quitar" asks and confirms; team restored |
| PRJ-06 | Pass after fix | search and Archivado filter as expected. Finding 1 |
| PRJ-07 | Pass | only Torre Norte, "Líder de equipo", no edit controls; Casa 50% says "Este proyecto no existe o no participas en él." |
| PLN-01 – 03 | Pass | duplicate category refused; totals $ 4.437.506,25 / $ 500.000 / $ 4.937.506,25, 32,4 % and 67,6 %, $ 35.000,50 kept; hint "$ 437.506,25"; the weight field suggests 60; 60,01 refused |
| PLN-04 – 06 | Pass | locked while sent; returned with the comment; Acero at $ 32.000 re-sent; approved: Activo, dates disabled with their hint, no delete buttons |
| PLN-07 | Pass | "En curso · Iniciada el 28 de sept de 2026"; Excavación green with date, Laura and the note; stage 40 %, Avance 12,4 %. No milestone was overdue (the plan starts Oct 1), so the red row was not seen |
| PLN-08 | Pass | no Reabrir for Laura; the admin's Reabrir puts the stage at 0 %; Carlos sees stages and milestones without amounts or buttons |
| FIN-01 – 04 | Pass | every figure as written; the empty Monto 1 and the over-draw messages sit under their fields |
| FIN-05 | Pass | the renamed text file refused with "Formato no permitido…"; the PDF's Comprobante opens (200) for the admin and for Laura, who has no Adjuntar or Anular |
| FIN-06 – 08 | Pass | void refused for TRX-001; DUP-1 voided grey with its reason; carry-over $ 1.500.000 → Estructura $ 1.650.000; Herramienta kept with "La categoría se usa en partidas o gastos…". Finding 2 |
| EXP-01 – 08 | Pass | as written, with the $ 750.000 limit: andamios waits for the admin; $ 910.000 refused with "Disponible: $ 300.000."; Estructura Disponible $ 1.551.000 then back to $ 1.651.000. The receipt of EXP-02 was attached after the rejection of EXP-03 (same checks, other order) |
| CAJ-01 – 04 | Pass | saldo $ 145.000; cycle 1 amber then green; Clavos refused with the closed-cycle message; the eye shows "Fin de mes" and who signed |
| DSH-01 – 02 | Pass | $ 955.000 · 20,6 %, CPI "1,51 · Bien", SPI "Sin datos" (nothing planned before Oct 1); the attention list, both charts, readable in Oscuro; Laura sees only Torre Norte, Carlos has no Tablero |
| AUD-01 | Pass | "Laura Gómez (vía Administrador)"; Registro › Gasto narrows the list; the eye shows status APPROVED → REIMBURSED; Laura's password changes show `***` → `***` |
| AUD-02 | Pass | no Auditoría for Laura; `/audit` sends her to her home |
| MAIL-01 – 03 | Pass | "Gasto por aprobar: Pintura" (button to `/projects/1?tab=expenses`), "Gasto rechazado: Pintura" with "Sin factura", "1 project digest(s) queued." and the digest to Laura and the super admin |

## Findings

1. **A project without dates showed "— – —" in the Proyectos list** (PRJ-06), the same pattern fixed on the
   dashboard in the monitoring phase. Fixed: a shared `formatDateRange` ("—" with no dates, "…" for a missing end)
   used by the project list and the stage cards.
2. **Removing a category asked with the bare name** (FIN-08). Fixed: "Se eliminará la categoría “Herramienta”.",
   like lines and milestones.
3. **Observation, not changed:** while viewing as someone, the "Ver como" list only offers "Nadie" and the current
   person after a page reload, because the candidates load while you are the super admin. Switching person means
   going back first, which is also what Symfony's switch_user requires.

## Conditions

- A production build made earlier for the deploy docs had emptied `public/build` of the dev files, and the watcher
  only rewrites changed files: the app went blank until the folder was cleared and the node container restarted.
- Two script steps ran before "Ver como" finished switching and acted as the super admin; one recorded an extra
  "Pintura", which was voided ("Registrado por error") before MAIL-01 was repeated as Carlos. It stays in the data
  as a voided expense.
- As in the earlier runs, the password manager extension blocks scripts and screenshots on pages with a password
  field.
