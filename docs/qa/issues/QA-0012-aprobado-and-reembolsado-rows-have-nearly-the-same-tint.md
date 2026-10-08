---
id: QA-0012
title: Aprobado and Reembolsado rows have nearly the same tint
category: ui
severity: P3
status: fixed
confidence: reproduced
area: project
view: Gastos
route: /projects/1?tab=expenses
found: 2026-10-07
last_seen: 2026-10-07
fixed_seen: 2026-10-08
commit: 4391ad0
related: []
---

# QA-0012 · Aprobado and Reembolsado rows have nearly the same tint

## Summary

`expense_approved` uses `success` (#e2f3e7) and `expense_reimbursed` uses `teal` (#dff1f0); on screen the two rows are indistinguishable except for the thin edge bar.

## Steps to reproduce

Account: admin@demo.test (super admin) · Data: `make seed` + the edge-case data of run 2026-10-07-admin-ui · Width: 1440 and 390 · Theme: light

1. /projects/1?tab=expenses: compare "Transporte de materiales" (Reembolsado) with "Clavos y alambre" (Aprobado).

## Expected

MDX §3: keep the values of one table on different tones, so the colour reads as the status.

## Actual

Two statuses, one apparent colour.

## Evidence

TONES in shared/ui/ui.tsx; tokens in app.css:23 and :27.

![desktop-torre-expenses](evidence/desktop-torre-expenses-crop.jpg)

## History

- 2026-10-07 · found in run 2026-10-07-admin-ui at 4391ad0
- 2026-10-08 · fixed in `ed38737` on `feature/ui-audit-fixes`; re-checked on its stack at 1440 and 390 px
