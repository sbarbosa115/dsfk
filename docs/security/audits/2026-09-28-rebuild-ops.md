# Security audit — rebuild-ops — 2026-09-28

- **Branch:** `feature/rebuild-ops`, against `feature/rebuild`
- **Scope:** `app:doctor` (run by the deploy script on the server); the demo data (`DemoFixtures`, `make seed`);
  the Documentación center (static help pages and screenshots); the deployment docs; two small UI fixes from the
  baseline run (date ranges, the category delete question).
- **Tools:** `composer audit`, `npm audit --omit=dev` clean (no dependency added). `audit.py`'s secret-looking
  lines are test values, labels and the demo password below.

## Leads

- [x] [A02/A05] `app:doctor` prints its checks to whoever runs it on the server: `APP_SECRET` is only measured,
      never printed; `MAILER_DSN` is shown without its credentials; the database row shows the server version,
      and a connection error is cut to 120 characters (MySQL's "Access denied for user…" names the user, never
      the password).
- [x] [A05] the demo data: `DemoFixtures` gives every demo account the password `demo1234`. The fixtures bundle is
      registered for `dev` and `test` only and is a `require-dev` package, so `composer install --no-dev` on the
      server cannot load it; `make seed` runs in the Docker stack only.
- [x] [A03] the help pages render fixed text from `model/content.ts` through React (no
      `dangerouslySetInnerHTML`); the search only filters that text. The screenshots are bundled images of demo
      data.
- [x] [A01] `/help` is a page of the React app: it shows inside the signed-in shell, but its text ships in the
      public JS bundle like every asset. That is acceptable because it describes screens and holds no data.

## Findings

| # | Severity | Category | Where | What was tried | Status |
|---|---|---|---|---|---|
| 1 | Low | A02 secret shown | `app:doctor`, MAILER_DSN row | A password containing "@" (`smtp://obra@example.com:p@ss@host`): the mask stopped at the first "@" and printed the rest of the password (the deploy docs say to URL-encode it, but a pasted one may not be) | Fixed: the mask runs to the last "@" before the host (`DoctorCommand::maskDsn`, unit test) |

## Checked, nothing found

- **Deployment docs** name the variables to set, never their values.
- **Screenshots** in the help show only demo people (`@demo.test`) and demo amounts.

## Not applicable

- A04, A07, A08, A10: no new endpoint, sign-in change, upload or outbound request in this phase.
