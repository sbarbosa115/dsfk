---
id: QA-0007
title: Dates, references and invoice numbers wrap mid-value
category: ui
severity: P2
status: open
confidence: reproduced
area: project
view: Fondos › Movimientos, Gastos
route: /projects/1?tab=finance, ?tab=expenses
found: 2026-10-07
last_seen: 2026-10-07
fixed_seen:
commit: 4391ad0
related: []
---

# QA-0007 · Dates, references and invoice numbers wrap mid-value

## Summary

Identifiers break across lines: "30 de ago de / 2026", "Transferencia · TRX- / 4602", "FE- / 000123456789", and "Fondos / de la / etapa" in three lines.

## Steps to reproduce

Account: admin@demo.test (super admin) · Data: `make seed` + the edge-case data of run 2026-10-07-admin-ui · Width: 1440 and 390 · Theme: light

1. /projects/1?tab=finance › Movimientos at 1440px.
2. /projects/1?tab=expenses, the first row (long supplier and invoice).

## Expected

MDX ux.md › Cells: "Identifiers do not wrap: document numbers, references, dates and amounts get `className=\"nowrap\"`."

## Actual

They wrap wherever the column is narrow, which is often because the Acciones column takes the width (see the row-actions issue).

## Evidence



![desktop-torre-finance](evidence/desktop-torre-finance-crop.jpg)

## History

- 2026-10-07 · found in run 2026-10-07-admin-ui at 4391ad0
