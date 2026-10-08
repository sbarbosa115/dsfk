---
id: QA-0014
title: Stage cards: loose controls row, floating weight total, misaligned stacked tables
category: ui
severity: P3
status: open
confidence: reproduced
area: project
view: Presupuesto y plan
route: /projects/1?tab=plan, /projects/2?tab=plan
found: 2026-10-07
last_seen: 2026-10-07
fixed_seen:
commit: 4391ad0
related: []
---

# QA-0014 · Stage cards: loose controls row, floating weight total, misaligned stacked tables

## Summary

Each stage's controls (pencil, ↑, ↓, Eliminar etapa / Iniciar etapa) sit alone on a row under the header; "Suma de pesos: 100%" floats mid-row; "Agregar hito" is disabled with no reason; the partidas tables of consecutive stages size their columns differently; units read "3 mes", "10 día".

## Steps to reproduce

Account: admin@demo.test (super admin) · Data: `make seed` + the edge-case data of run 2026-10-07-admin-ui · Width: 1440 and 390 · Theme: light

1. /projects/2?tab=plan (draft budget).
2. /projects/1?tab=plan (approved).

## Expected

Actions in the card header's action slot; disabled controls say why (skill 3.2 › Feedback); the same table in consecutive cards has the same column widths.

## Actual

As described.

## Evidence



![desktop-casa-plan](evidence/desktop-casa-plan-crop.jpg)

## History

- 2026-10-07 · found in run 2026-10-07-admin-ui at 4391ad0
