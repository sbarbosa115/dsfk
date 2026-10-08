---
id: QA-0004
title: The same row action is shown three ways: worded, icon-only, or missing, in a different order per row
category: ui
severity: P2
status: open
confidence: reproduced
area: project
view: Row actions in Gastos, Fondos, Usuarios, Plan
route: /projects/1?tab=expenses, ?tab=finance, /users
found: 2026-10-07
last_seen: 2026-10-07
fixed_seen:
commit: 4391ad0
related: []
---

# QA-0004 · The same row action is shown three ways: worded, icon-only, or missing, in a different order per row

## Summary

Voiding is the worded red "Anular" in Gastos but an icon-only ban in Fondos › Movimientos; approve and reject in Gastos are icon-only (check / ban); a row shows from 1 to 5 buttons in varying order, with the destructive one sometimes in the middle.

## Steps to reproduce

Account: admin@demo.test (super admin) · Data: `make seed` + the edge-case data of run 2026-10-07-admin-ui · Width: 1440 and 390 · Theme: light

1. /projects/1?tab=expenses: compare the rows "Cinta y señalización" (eye, ✓, ⦸, Adjuntar recibo, Recibo), "Amarres y separadores" (eye, Recibo, Anular) and "Concreto 3000 PSI" (eye, Adjuntar recibo, Anular).
2. /projects/1?tab=finance › Movimientos: void is an icon-only ⦸; attach is "Adjuntar" (vs "Adjuntar recibo" in Gastos).

## Expected

MDX ux.md checklist: "Icon-only buttons are only view, edit and on/off; actions follow the one order, destructive last." One word per action across tables (Anular / Adjuntar …).

## Actual

Reject (⦸) sits second, before Adjuntar recibo; approve is an unlabeled tick; void is worded in one table and an icon in the next. The Acciones column is ~30% of the table width, which pushes Pagado/Gasto into 3-4 line cells.

## Evidence

Seen at 1440px light and dark.

![desktop-torre-expenses](evidence/desktop-torre-expenses-crop.jpg)

![desktop-torre-finance](evidence/desktop-torre-finance-crop.jpg)

## History

- 2026-10-07 · found in run 2026-10-07-admin-ui at 4391ad0
