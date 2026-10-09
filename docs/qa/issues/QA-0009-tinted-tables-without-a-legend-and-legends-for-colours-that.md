---
id: QA-0009
title: Tinted tables without a legend, and legends for colours that cannot appear
category: ui
severity: P3
status: fixed
confidence: reproduced
area: project
view: Tablero › Etapas, Fondos › Fondos por etapa, Plan › Hitos, Caja menor
route: /projects/:id
found: 2026-10-07
last_seen: 2026-10-07
fixed_seen: 2026-10-08
commit: 4391ad0
related: []
---

# QA-0009 · Tinted tables without a legend, and legends for colours that cannot appear

## Summary

The stage tables on Tablero and Fondos tint rows by stage status (green / blue / grey) with no "Color de la fila" legend; the Hitos legend appears on draft budgets where no row is tinted; Ciclos anteriores lists "Abierto", which never appears there (the open cycle is shown above).

## Steps to reproduce

Account: admin@demo.test (super admin) · Data: `make seed` + the edge-case data of run 2026-10-07-admin-ui · Width: 1440 and 390 · Theme: light

1. /projects/1?tab=dashboard › Etapas; ?tab=finance › Fondos por etapa.
2. /projects/2?tab=plan › Hitos.
3. /projects/1?tab=petty-cash › Ciclos anteriores.

## Expected

MDX CLAUDE.md §3: "Put `<RowLegend statuses={…} />` above every tinted table, listing every value it can show".

## Actual

Missing in two tables, meaningless in two others.

## Evidence



![desktop-torre-finance](evidence/desktop-torre-finance-crop.jpg)

## History

- 2026-10-07 · found in run 2026-10-07-admin-ui at 4391ad0
- 2026-10-08 · fixed in `ed38737` on `feature/ui-audit-fixes`; re-checked on its stack at 1440 and 390 px
