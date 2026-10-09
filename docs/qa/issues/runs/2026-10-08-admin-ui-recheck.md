# QA run · 2026-10-08 · admin-ui-recheck

- **Scope:** admin-ui-recheck
- **Commit:** edb7ab0 on `feature/ui-audit-fixes`
- **Stack:** the `feature/ui-audit-fixes` worktree's stack (http://localhost:18082), reset to the demo seed plus the 2026-10-07 edge-case data
- **Accounts:** Admin (super admin) admin@demo.test; Team Lead lider2@demo.test for the correction flow
- **Widths / themes:** 1440 and 390 · light (dark spot-checked in the original run; colours come from the same tokens)
- **Reference screens:** the same as the 2026-10-07 run

## Covered

| View | Role(s) | UI | Functional | Notes |
|---|---|---|---|---|

## Not covered

- Not covered this time: dark theme sweep, tablet width, PM and Team Lead views (as in the audit).

## New issues

| Id | Priority | Category | View | Title |
|---|---|---|---|---|

## Seen again (still open)

- None

## Found fixed

- QA-0001 – QA-0017, each in its commit on `feature/ui-audit-fixes` (see each issue's History); re-checked by the smoke suite (UI-01 – UI-10) and by screenshots at 1440 and 390 px. The manual run is `docs/tests/runs/2026-10-08-ui-audit-fixes.md`.

## Regressions (fixed before, back now)

- None

## Unclear: questions for the user

- Whether COP should round on screen was answered by default (whole pesos, exact in modals and tooltips); README › Data model decisions says how to undo it.
