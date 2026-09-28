# UI regression run — 2026-09-28 (rebuild-monitoring)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), with sections 10 (Tablero), 11 (Auditoría) and 12
  (Correos) added in this phase.
- **Branch:** `feature/rebuild-monitoring`, this checkout's stack (app :18091, Mailpit :18035, the worker
  consuming the queue).
- **Data:** continued from the expenses run (not reset), plus its scratch project "Prueba concurrencia".
- **How:** Chrome through the real UI ("Ver como" to switch people; fields set from the page with native input
  events), Mailpit's API for the emails, the console command for the digest.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 59 |
| Pass | 7 (DSH-01 – 02, AUD-01 – 02, MAIL-01 – 03) |
| Fail | 0 |
| Not run | 52 (sections 1–9: passed in the earlier runs of this rebuild; nothing of theirs changed here) |

## Results

| IDs | Result | Notes |
|---|---|---|
| DSH-01 | Pass | cards for Prueba concurrencia and Torre Norte; Torre Norte $ 4.637.506,25, $ 755.000 · 16,3 %, CPI "1,90 · Bien", SPI "Sin datos" (today, Sept 28, is before the plan's first date, so nothing is planned yet); links to `?tab=dashboard`. Laura also sees Prueba concurrencia, where the scratch script made her PM; Carlos gets an empty portfolio and 403 on a project dashboard |
| DSH-02 | Pass after fix | tiles, "1 gasto aprobado falta por reembolsar.", both charts, stage table; "Ver como tabla" gives Sept 2026 $ 2.001.000 / $ 755.000; readable in Oscuro. Findings 1–3 |
| AUD-01 | Pass | the trail started with this phase: the Pintura changes of MAIL-01/02 show as "Carlos Pérez (vía Administrador)" and "Laura Gómez (vía Administrador)"; Registro › Gasto narrows it; the eye shows status SUBMITTED → REJECTED and the reason. The masked password is covered by `AuditApiTest` (no password was changed in this run) |
| AUD-02 | Pass | no Auditoría for Laura; `/api/audit` 403 |
| MAIL-01 | Pass | "Gasto por aprobar: Pintura" to pm@demo.test, naming Carlos, $ 30.000, Estructura · Materiales; the button opens `/projects/1?tab=expenses` on :18091 |
| MAIL-02 | Pass | "Gasto rechazado: Pintura" to lider@demo.test with "Sin factura" |
| MAIL-03 | Pass | "1 project digest(s) queued."; "Resumen diario: Torre Norte" to Laura and the super admin with "Gastos aprobados por reembolsar: 1" (no late milestones yet: the plan's dates are in the future) |

## Findings

1. **The indices tile read its badges before their names** (DSH-02). Fixed: "CPI (costo) · 1,90 · Bien" on one
   line each.
2. **A stage not started showed "— – —" as its dates** (DSH-02). Fixed: "—" when it has no date, "1 oct – …"
   while it runs.
3. **The stage chart's "Ver como tabla" wrapped under its hint.** Fixed with the card header's layout.

## Conditions

- As in the earlier runs: field values set from the page (the extension holds keystrokes); the console showed only
  the extension's "reading 'global'" error; one screenshot timed out while a page loaded.
