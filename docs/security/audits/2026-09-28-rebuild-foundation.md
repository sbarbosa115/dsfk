# Security audit — rebuild-foundation — 2026-09-28

- **Branch:** `feature/rebuild-foundation` at `d033b3d`, against `origin/main`
- **Scope:** the Shared kernel (CSRF header check, API error rendering, SPA fallback route), config (security
  placeholder, messenger buses, NelmioApiDoc), new dev dependencies (PHP-CS-Fixer, PHPStan, Deptrac, ESLint,
  Prettier, openapi-typescript), the `worker` service, CI.
- **Tools:**
  - `composer audit`: clean
  - `npm audit --omit=dev`: 0 vulnerabilities
  - secrets in the diff: 0 found

## Leads from audit.py

- [x] [A08 Path traversal] `SpaController.php:29`: the path is `%kernel.project_dir%/public/app/index.html`, a
      constant; the request path is never used to build it. Nothing found.
- [x] [A10 SSRF] `SpaController.php:29`: `file_get_contents` of the constant local file above, no URL. Nothing found.
- [x] [A10 SSRF] `PlumbingTest.php:15,25,35`: test client requests to the kernel, not the network. Not applicable.
- [x] [A01 Access control] `SpaController.php:21`: public on purpose (serves the static SPA shell, no data);
      `^(?!api/|_)` keeps it off `/api/` so unknown API paths get a JSON 404 (tested).
- [x] [A05 Config] `security.yaml`: temporary placeholder (empty in-memory provider, every `/api` path needs
      `ROLE_USER`, so everything under `/api` is closed). Replaced by the Identity phase.

## Findings

| # | Severity | Category | Where | What an attacker could do | Status |
|---|---|---|---|---|---|
| 1 | Low | A05 Config | `config/bundles.php` | NelmioApiDocBundle was enabled in every environment; no doc route was exposed, but the checklist wants API doc bundles dev-only. | Fixed: bundle and config limited to dev/test |

## Checked, nothing found

- **A03 Injection:** no queries, no shell, no templates rendered in this phase.
- **A05 Config:** API errors outside debug return generic codes, never framework messages (snake_case codes
  only); `detail` is added only when `kernel.debug` is on. Security headers stay in `public/.htaccess`
  (unchanged). Session cookie config unchanged (`HttpOnly`, `SameSite=Lax`, `Secure` auto).
- **A07 CSRF:** every non-safe `/api/` request without `X-Requested-With: XMLHttpRequest` is refused with 403
  before routing (tested: `PlumbingTest`).
- **A06:** new dependencies are dev-only (except NelmioApiDocBundle, now dev/test only), maintained, and pinned
  by the lock files. ESLint pinned to 9 because `eslint-plugin-boundaries` 7 does not support 10 yet;
  `openapi-typescript` is told to use the project's TypeScript 6 (its peer range says 5).

## Not applicable

- A02, A04, A08 (uploads), A09: no authentication, uploads, rate limits or logging of user actions in this phase.
