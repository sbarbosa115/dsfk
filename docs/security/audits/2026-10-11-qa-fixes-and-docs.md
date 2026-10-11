# Security audit — qa-fixes-and-docs — 2026-10-11

- **Branch:** `feature/qa-fixes-and-docs` at `2b36a50`, against `origin/main`
- **Scope:** no route, input or permission change. Two read-only fields in `GET /projects/{id}/finance` (`projectFund.received`, `pettyCash.received`, both already derivable from what PM and Admin see), CSS for tables at 601–1279 px, a ResizeObserver in `DataTable`, help text and docs.
- **Tools:**
  - `composer audit`: clean
  - `npm audit --omit=dev`: clean
  - secrets in the diff: 0 found

## Leads from audit.py

Each one checked against the checklist in `docs/security/README.md` and resolved into a finding or "nothing found".

- none

## Findings

| # | Severity | Category | Where | What an attacker could do | Status |
|---|---|---|---|---|---|

None.

## Checked, nothing found

- **A01 Access control:** the new fields ride on `GET /finance`, already 403 for Team Leads and 404 for other projects (existing tests).
- **A03 XSS:** help text is static JSON rendered by React as text.

## Not applicable

- **A02, A04–A10:** nothing new: no writes, secrets, config, dependencies, sign-in, uploads or outbound calls.
