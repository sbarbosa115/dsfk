# Security audit — rebuild-expenses — 2026-09-28

- **Branch:** `feature/rebuild-expenses`, against `feature/rebuild`
- **Scope:** the Expense API (`GET|POST /projects/{id}/expenses`, `GET|PUT /expenses/{id}`, `POST /expenses/{id}/`
  `approve|reject|void|attachments`, `POST /projects/{id}/reimbursements`); the caja menor API
  (`GET /projects/{id}/petty-cash`, `GET /petty-cash-cycles/{id}`, `POST /projects/{id}/petty-cash/close`,
  `POST /petty-cash-cycles/{id}/sign-off`); receipts through `GET /attachments/{id}`; the Gastos and Caja menor
  tabs.
- **Tools:** `composer audit` and `npm audit --omit=dev` clean (no dependency added); `audit.py` compares with
  `origin/main`, so only this phase's routes and uploads were checked here.

## Leads

- [x] [A01] every new route resolves the project (from the path, the expense or the cycle) and runs `ProjectGuard`
      first: outside the project 404 `project_not_found`. Expense decisions need `PLAN` (PM, Admins), voids and
      sign-offs `ADMINISTER`, the caja menor `VIEW_FINANCIALS`. A Team Lead sees and changes only their own
      expenses: another's is 404 `expense_not_found` (not 403), in the list too. Tested in `ExpenseApiTest`
      (`…OutsidersDoNotReach…`, `…RejectedExpenseIsCorrected…`, `…TeamLeadNoLongerChanges…`) and
      `PettyCashApiTest` (`…AnotherProjectsPeopleDoNotReachItsCycles`).
- [x] [A08 Uploads] receipts go through the same Document checks as proofs (type by content, 10 MB, random name,
      outside `public/`); a Team Lead opens their own receipts and nobody else's (tested 200/200/403).

## Findings

| # | Severity | Category | Where | What was tried | Status |
|---|---|---|---|---|---|
| 1 | Medium | A04 race | reimburse, void, approve | Two reimbursements of the same approved expenses sent at once both passed the status check before either committed: the Team Lead would be paid twice from the caja menor. A void racing a reimbursement had the same shape | Fixed: command handlers load the expenses they change with `SELECT … FOR UPDATE` (`forUpdate` / `manyForUpdate`), and a movement is locked before it is voided; the second request then sees the first's result (409 / 422). Checked on the Docker stack with two parallel requests (regression run) |
| 2 | — | A01 IDOR | `POST /projects/{B}/reimbursements` | The PM of project B pays back project A's expense by id from B's caja menor | Refused: 422 on `expenseIds` (test added) |
| 3 | — | A01 privilege | Team Lead | Approve, reimburse, pay from a stage, add receipts after approval, correct an approved expense | Refused: 403 / 422 / 409 (tests) |

## Checked, nothing found

- **A04 business rules on the server:** spending needs an approved budget; the PM and Admins pay from a stage or
  the caja menor, Team Leads only out of pocket; no account goes below zero (`insufficient_funds` with what is
  available); approval needs a receipt; the PM approves up to the Team Lead limit, an Admin above it; only the
  owner corrects, and only while pending or rejected; only approved expenses not paid back are voided (the money
  goes back); only approved out-of-pocket expenses of the project are paid back; a closed cycle's movements are
  final; a cycle is signed off once.
- **A01 data exposure:** Team Leads get their own expenses only, never the caja menor or the finance summary; the
  summary counts (pending, to reimburse) are their own.
- **A03:** the list's `status` filter is parsed into the enum (unknown values are a 422), the search is bound and
  escaped.

## Accepted

- As in the finance phase, a receipt's file is moved into place before the transaction commits.

## Not applicable

- A02, A07 (no auth change), A10.
