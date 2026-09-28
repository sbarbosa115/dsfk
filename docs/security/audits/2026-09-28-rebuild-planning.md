# Security audit — rebuild-planning — 2026-09-28

- **Branch:** `feature/rebuild-planning`, against `feature/rebuild`
- **Scope:** the Plan API (`GET /projects/{id}/plan`; categories, stages, order, contingency, submit, return,
  approve; `/stages/{id}` (PATCH, DELETE, start, lines, milestones); `/budget-lines/{id}`; `/categories/{id}`;
  `/milestones/{id}` (PATCH, DELETE, complete, reopen)); the Presupuesto y plan tab.
- **Tools:** `composer audit` clean; `npm audit --omit=dev` clean; secrets grep 0 lines; the SSRF leads are the
  tests' kernel requests.

## Leads from audit.py

- [x] [A01] every new route (26 leads over the five Plan controllers): each resolves the project from the part's
      id (`PlanQueries::projectOf*`) and runs `ProjectGuard` before dispatching: outside the project → 404
      `project_not_found`; wrong role → 403 (Team Lead writing; PM approving, returning or reopening). An unknown
      part is 404 with its own code (`stage_not_found`…). Tested: `testOutsidersDoNotLearnThePlanOrItsPartsExist`,
      `testATeamLeadSeesThePlanWithoutMoneyAndCannotEditIt`, `testTheApprovalWorkflowLocksTheBudgetForEveryone`,
      `testAProjectManagerCannotReachAnotherProjectsPlanThroughItsIds` (added in this audit).

## Findings

None open.

| # | Severity | Category | Where | What was tried | Status |
|---|---|---|---|---|---|
| 1 | — | A01 IDOR | `PATCH /stages/{id}`, `PUT /projects/{id}/stages/order` | A PM of project A renames project B's stage by id, and reorders A with B's stage id in the list | Refused: 404 for the stage, 422 for the order (the list must be exactly A's stages); B unchanged (test added) |

## Checked, nothing found

- **A01 data exposure:** Team Leads get the plan with every amount null (`budget`, `lines`, `budgetTotal`,
  `weight`, `issues`), tested; the write endpoints are closed to them, so they never see a plan through a write.
- **A01 cross-project data:** a line cannot use another project's category (422, tested).
- **A04 business rules enforced by the domain, not the UI:** nobody edits a submitted or approved budget, the
  Admin included (409 `budget_locked`); submit and approve re-check completeness on the server; milestones are
  met only after approval and never in the future; weights cannot pass 100 %; a completed stage's milestones
  cannot be reopened.
- **A03:** no SQL/DQL built from input; amounts, quantities and percentages are parsed with exact decimals
  (brick/math), never floats, and refused past their scale.
- **Frontend:** amounts are rendered by React (escaped); no `dangerouslySetInnerHTML`.

## Not applicable

- A02, A07, A08 (no uploads), A10.
