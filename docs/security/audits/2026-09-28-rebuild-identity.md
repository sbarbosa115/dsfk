# Security audit — rebuild-identity — 2026-09-28

- **Branch:** `feature/rebuild-identity` at `f48557c`, against `feature/rebuild`
- **Scope:** <!-- routes, inputs, uploads, rendered content, jobs, dependencies this feature added or changed -->
- **Tools:**
  - `composer audit`: clean
  - secrets in the diff: 5 found (see Findings)

## Leads from audit.py

Each one checked against the checklist in `docs/security/README.md` and resolved into a finding or "nothing found".

- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:24`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:32`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:35`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:43`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:50`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:58`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:65`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:67`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:70`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:86`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:90`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:99`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/AuthTest.php:101`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/ImpersonationTest.php:33`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/ImpersonationTest.php:48`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/ImpersonationTest.php:51`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/ImpersonationTest.php:64`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/ImpersonationTest.php:69`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/ImpersonationTest.php:79`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/UserApiTest.php:16`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/UserApiTest.php:18`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/UserApiTest.php:37`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/UserApiTest.php:70`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/UserApiTest.php:90`: server-side request: is the URL user-supplied?
- [ ] [A10 SSRF] `backend/tests/Functional/Api/UserApiTest.php:120`: server-side request: is the URL user-supplied?
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/AuthController.php:31`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/AuthController.php:45`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/AuthController.php:52`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/AuthController.php:63`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/AuthController.php:73`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/UserController.php:28`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/UserController.php:42`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/UserController.php:54`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/UserController.php:66`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Settings/UI/Http/Controller/SettingsController.php:20`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Settings/UI/Http/Controller/SettingsController.php:28`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/src/Settings/UI/Http/Controller/SettingsController.php:36`: new route: firewall, role and ownership checked, and a test for another tenant
- [ ] [A01 Access control] `backend/config/packages/security.yaml:34`: access check removed or loosened?
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/UserController.php:25`: access check removed or loosened?
- [ ] [A01 Access control] `backend/src/Identity/UI/Http/Controller/UserController.php:29`: access check removed or loosened?
- [ ] [A01 Access control] `backend/src/Settings/UI/Http/Controller/SettingsController.php:18`: access check removed or loosened?
- [ ] [A01 Access control] `backend/src/Settings/UI/Http/Controller/SettingsController.php:37`: access check removed or loosened?
- [ ] [A05 Config] `backend/config/packages/security.yaml:3`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:6`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:7`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:15`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:16`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:17`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:18`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:19`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:20`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:21`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:22`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:23`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:24`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:25`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:26`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:27`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:28`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:29`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:30`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:31`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:34`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:37`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:38`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:39`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:40`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:41`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:42`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:43`: security/CORS/framework config changed
- [ ] [A05 Config] `backend/config/packages/security.yaml:44`: security/CORS/framework config changed
- [ ] [Frontend] `frontend/src/app/guards.tsx:7`: open redirect: only follow same-origin relative paths
- [ ] [Frontend] `frontend/src/features/auth-login/ui/LoginForm.tsx:13`: open redirect: only follow same-origin relative paths

## Findings

| # | Severity | Category | Where | What an attacker could do | Status |
|---|---|---|---|---|---|

## Checked, nothing found

<!-- every checklist section that applies, with what was checked -->

## Not applicable

<!-- sections that do not apply, and why -->
