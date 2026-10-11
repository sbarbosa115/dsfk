# Security audit — budget-amendment — 2026-10-11

- **Branch:** `feature/budget-amendment` at `d953609`, against `origin/main`
- **Scope:** no new route. `POST /projects/{id}/stages` now succeeds on an approved budget (into an addition); the stage, line and milestone edits, `budget/submit|return|approve`, `stages/{id}/start` and `milestones/{id}/complete` gained amendment rules; new fields in `GET /projects/{id}/plan`; the budget emails name the addition. One migration (table `budget_amendment`, two nullable columns). No uploads, jobs or dependencies.
- **Tools:**
  - `composer audit`: clean
  - `npm audit --omit=dev`: clean
  - secrets in the diff: 0 found

## Leads from audit.py

Each one checked against the checklist in `docs/security/README.md` and resolved into a finding or "nothing found".

- [x] [A08 Uploads] `backend/src/Planning/Domain/Model/Budget.php:234`: file upload handling: MIME from content, size limit, stored outside the web root → nothing found: `Budget::move()` changes a status; no file is involved.
- [x] [A08 Uploads] `backend/src/Planning/Domain/Model/Budget.php:248`: file upload handling: MIME from content, size limit, stored outside the web root → nothing found: `Budget::move()` changes a status; no file is involved.
- [x] [A08 Uploads] `backend/src/Planning/Domain/Model/Budget.php:262`: file upload handling: MIME from content, size limit, stored outside the web root → nothing found: `Budget::move()` changes a status; no file is involved.
- [x] [A10 SSRF] `backend/tests/Functional/Api/ExpenseApiTest.php:64`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/NotificationTest.php:72`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/NotificationTest.php:78`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:284`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:307`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:309`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:316`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:323`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:327`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:343`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.
- [x] [A10 SSRF] `backend/tests/Functional/Api/PlanApiTest.php:360`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app.

## Findings

| # | Severity | Category | Where | What an attacker could do | Status |
|---|---|---|---|---|---|

None.

## Checked, nothing found

- **A01 Broken access control:** adding a stage after approval uses the same `PLAN` permission as before approval (PM or Admin of the project); approving or returning an addition the same `ADMINISTER` as the budget (`PlanApiTest`: the Admin reviews; the existing tests keep the PM at 403 on approve, the Team Lead at 403 on every write, and another project's ids at 404).
- **A04 Insecure design (budget integrity):** approved stages, lines and milestones stay locked (`Budget::assertStageEditable`, tested on a line of an approved stage → 409); only one open addition, locked while submitted; the contingency and the stage order cannot change through an addition; a pending stage cannot be started, have milestones met, receive money or expenses (`stage_not_approved`, and the plan directory leaves it out: `FinanceApiTest`, `ExpenseApiTest`).
- **A03 Injection / XSS:** stage names and the return comment are rendered by React and Twig as text (the email template escapes as before); no string-built query.
- **A09 Logging:** `BudgetAmendment` is `Audited`; its transitions are `BudgetEvent`s with who, when and the comment.

## Not applicable

- **A02, A05, A06, A07, A08, A10:** nothing new: no secrets, config, dependencies, sign-in, uploads or outbound calls.
