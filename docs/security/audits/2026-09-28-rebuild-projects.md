# Security audit — rebuild-projects (and the move to Symfony UX) — 2026-09-28

- **Branch:** `feature/rebuild-projects` (with `feature/rebuild-ux` merged), against `feature/rebuild`
- **Scope:** `GET|POST /api/projects` (`?q=`, `?status=`, paging), `GET|PATCH /api/projects/{id}`,
  `POST /api/projects/{id}/members`, `DELETE /api/projects/{id}/members/{memberId}`; the project voter and
  `ProjectGuard`; the users list's new `?q=`/`?status=`; the SPA now rendered by Twig (`spa.html.twig`) with
  Symfony UX React and Webpack Encore (new npm and Composer packages); the Projects screens.
- **Tools:** `composer audit` clean; `npm audit --omit=dev` clean; secrets grep: 0 lines. The SSRF leads are the
  functional tests' requests to the kernel.

## Leads from audit.py

- [x] [A01] `ProjectController` routes (lines 37–131): every route but the list and create starts with
      `ProjectGuard::require()`: someone outside the project gets 404 `project_not_found` (tested with another
      project's id and an unknown id), a member without the permission 403 (tested: a PM editing, a PM adding
      members). Create is `#[IsGranted('ROLE_ADMIN')]` (tested). The list is filtered to the person's projects
      unless admin (tested).
- [x] [A01] "access check removed or loosened?" at lines 35 and 65: the lines are the list route and `show()`;
      nothing was loosened (the previous implementation answered 403 to non-members; now it is 404).

## Findings

None open. Checked by trying the attacks as tests:

| # | Severity | Category | Where | What was tried | Status |
|---|---|---|---|---|---|
| 1 | — | A01 IDOR | `DELETE /projects/{id}/members/{memberId}` | Removing a member of project B through project A's URL | Refused: 404 `member_not_found`, B keeps the member (test added) |
| 2 | — | A04 | `GET /projects?status=` | A status outside the enum | Client error, not a 500 (test added) |

## Checked, nothing found

- **A01:** a Team Lead cannot see another project (404); admins cannot be added as members (`admin_is_global`)
  and disabled users cannot either (`user_inactive`); a project keeps one PM (409).
- **A03:** the name search is a bound parameter with `%`, `_` and the escape character escaped (tested with
  `50%` and `ana_p`); no SQL or DQL is built from input. React escapes every value; no
  `dangerouslySetInnerHTML`.
- **A04:** Input DTOs list exactly the fields an admin may set; the currency cannot change (`currency_locked`).
- **A05:** the SPA page is a Twig template with no user data in it (only the static `react_component('App')`);
  `Cache-Control: no-cache` so a new build is picked up. The build output (`public/build`) holds no secrets
  (no env is compiled into the bundle).
- **A06:** new dependencies (symfony/ux-react, symfony/webpack-encore-bundle, symfony/stimulus-bundle,
  @symfony/webpack-encore, react-number-format) are maintained and locked; both audits clean. npm 11 wrote the
  lock file (npm 10 crashes resolving the tree); the containers and the deploy use `npm ci`, which only reads it.

## Not applicable

- A02, A07 (no auth changes), A08 (no uploads yet), A10.
