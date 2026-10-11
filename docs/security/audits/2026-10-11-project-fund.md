# Security audit — project-fund — 2026-10-11

- **Branch:** `feature/project-fund` at `13d7c48`, against `origin/main`
- **Scope:** new `POST /api/projects/{id}/fund/assignments` (destination, stageId, amount, date, note); new optional `toStageId` on `POST /api/stages/{id}/complete`; a new deposit destination `PROJECT_FUND`; new fields in `GET /api/projects/{id}/finance`; the UI's "Asignar del fondo" modal and the "Destino del saldo" select. No uploads, emails, jobs, dependencies or config.
- **Tools:**
  - `composer audit`: clean
  - `npm audit --omit=dev`: clean
  - secrets in the diff: 0 found

## Leads from audit.py

Each one checked against the checklist in `docs/security/README.md` and resolved into a finding or "nothing found".

- [x] [A10 SSRF] `backend/tests/Functional/Api/FinanceApiTest.php:257`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app's routes, no outbound request.
- [x] [A10 SSRF] `backend/tests/Functional/Api/FinanceApiTest.php:348`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app's routes, no outbound request.
- [x] [A10 SSRF] `backend/tests/Functional/Api/FinanceApiTest.php:370`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app's routes, no outbound request.
- [x] [A10 SSRF] `backend/tests/Functional/Api/FinanceApiTest.php:373`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app's routes, no outbound request.
- [x] [A10 SSRF] `backend/tests/Functional/Api/FinanceApiTest.php:482`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app's routes, no outbound request.
- [x] [A10 SSRF] `backend/tests/Functional/Api/FinanceApiTest.php:490`: server-side request: is the URL user-supplied? → nothing found: the functional tests' own client calling the app's routes, no outbound request.
- [x] [A01 Access control] `backend/src/Finance/UI/Http/Controller/FinanceController.php:113`: new route: firewall, role and ownership checked, and a test for another tenant → nothing found: `ProjectGuard::require(ADMINISTER)` (PM 403, other project's PM 404, tested); `stageId` and `toStageId` only resolve among the project's own stages, so another project's stage is 422 (tested in `testAnotherProjectsPeopleCannotReachThisProjectsMoneyByItsIds`).

## Findings

| # | Severity | Category | Where | What an attacker could do | Status |
|---|---|---|---|---|---|

None.

## Checked, nothing found

- **A01 Broken access control:** both writes are Admin-only through `ProjectGuard`; Team Leads still get 403 on `GET /finance`; ids from another project are refused (above).
- **A03 Injection / XSS:** the note is bound by Doctrine and rendered by React as text; no `dangerouslySetInnerHTML`, no string-built queries (the one changed query uses bound `:types`).
- **A04 Insecure design (money):** the fund's cap is checked inside the project's ledger lock (`LedgerRepository::lock`), as deposits and draws are, so two concurrent assignments cannot overdraw it; voiding refuses any overdraw; carry-overs stay non-voidable; expenses cannot be paid from the fund (`FundMovement::spend` throws).
- **A08 Integrity:** `destination` is an enum; only `STAGE` and `PETTY_CASH` are accepted for an assignment (422 otherwise, tested); amounts go through `Patterns::AMOUNT` and the currency's minor units.
- **A09 Logging:** `FundMovement` is `Audited`, so assignments and their voids land in Auditoría like the other movements.

## Not applicable

- **A02 Crypto, A05 Config, A06 Components, A07 Auth, A10 SSRF:** nothing new: no secrets, config, dependencies, login changes or outbound requests.
