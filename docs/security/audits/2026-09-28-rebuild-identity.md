# Security audit — rebuild-identity — 2026-09-28

- **Branch:** `feature/rebuild-identity`, against `feature/rebuild`
- **Scope:** `POST /api/login`, `POST /api/logout`, `GET /api/me`, `POST /api/me/password`,
  `GET|POST /api/impersonate` (switch_user), `GET|POST /api/users`, `PATCH /api/users/{id}`,
  `GET|PUT /api/settings`, `app:create-admin`; the security config (provider, json_login, throttling,
  switch_user); the login, users, settings and "Ver como" screens.
- **Tools:** `composer audit` clean, `npm audit --omit=dev` 0 vulnerabilities, secrets grep 0 found,
  `debug:firewall main` reviewed.

## Leads from audit.py

- [x] [A05 Config] `security.yaml` (every line): reviewed as a whole. `/api/login` is the only public API
      path; everything else under `/api` needs `ROLE_USER`; admin endpoints add `#[IsGranted('ROLE_ADMIN')]`;
      switch_user needs `CAN_SWITCH_USER` (ImpersonationVoter: super admin, flag on, active non-admin target).
      The weaker hasher config is under `when@test` only.
- [x] [Frontend] `guards.tsx:7`, `LoginForm.tsx:13`: `from` comes from router state set by our own guard,
      never from the URL. Tightened anyway to relative paths that do not start with `//` (`1c6ecae`).

## Findings

| # | Severity | Category | Where | What an attacker could do | Status |
|---|---|---|---|---|---|
| 1 | High | A01 Access control | `PATCH /api/users/{id}` | An ordinary admin could reset a super admin's password or email and then sign in as them, gaining "Ver como" and the power to grant super admin. (The previous implementation had the same hole.) | Fixed in `1c6ecae`: `User::assertEditableBy()`; tests `testAnOrdinaryAdminCannotTakeOverASuperAdminAccount`, `UserTest::testOnlyASuperAdminEditsAnotherSuperAdmin`; the edit button is disabled for them in the UI |
| 2 | Low | A09 Logging | login | Failed sign-ins were not logged, so a password-guessing run left no trace beyond the throttle. | Fixed in `1c6ecae`: warning with code, email and IP (never the password) |

## Checked, nothing found

- **A01:** users and settings writes refuse non-admins (403, tested); users list likewise; an unknown user id
  is 404 `user_not_found`; "Ver como" refused for plain admins, admins as targets, disabled users and
  oneself (tested); switching by GET or without the CSRF header is refused (tested); acting as a PM closes
  admin endpoints (tested). Nobody removes their own admin access or disables themselves (tested).
- **A02:** passwords hashed with the `auto` hasher; `app:create-admin --generate-password` uses
  `random_bytes`; nothing sensitive logged (the failure log has no password).
- **A04:** Input DTOs list exactly the fields a user may set (`CreateUserInput`, `UpdateUserInput`,
  `SettingsInput`); entities are never deserialised from the request. Login throttled at 5 attempts per
  15 minutes per user and IP (tested).
- **A07:** unknown email and wrong password get the same `invalid_credentials` (tested). The session id is
  migrated on login (Symfony default). Changing a password, the roles or disabling a user ends that user's
  other sessions (`SecurityUser::isEqualTo`; tested for disabling). State-changing calls need the CSRF header.
- **A03:** no queries built from input (Doctrine `findOneBy`/`find` only), no HTML rendered from user input
  (React escapes; no `dangerouslySetInnerHTML`).

## Not applicable

- A08 uploads, A10 SSRF: none in this phase.
