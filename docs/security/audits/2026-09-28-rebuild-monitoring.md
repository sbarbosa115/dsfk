# Security audit — rebuild-monitoring — 2026-09-28

- **Branch:** `feature/rebuild-monitoring`, against `feature/rebuild`
- **Scope:** the audit trail (`AuditTrail` listener, `GET /audit`, the Auditoría page); the dashboards
  (`GET /dashboard`, `GET /projects/{id}/dashboard`, the Tablero page and tab); the notification emails (event
  handlers, `TemplatedOutbox`, `app:alerts:daily`, the event-bus failure middleware).
- **Tools:** `composer audit`, `npm audit --omit=dev` clean (no dependency added).

## Leads

- [x] [A01] `GET /audit`: `ROLE_ADMIN` (403 for a PM, tested). The trail covers every project, as Admins do.
- [x] [A01] `GET /projects/{id}/dashboard`: `VIEW_FINANCIALS` (Team Lead 403, outsider 404, tested);
      `GET /dashboard` lists only projects whose money the person may see (Team Lead: none; PM: theirs;
      Admin: all — tested).
- [x] [A02/A09] the trail never stores a password (`password` masked as `***`, tested); it records who, and
      while an Admin views the app as someone else, both ("Pm (vía Admin)", tested).
- [x] [A03] emails: Twig autoescapes the body (a description with HTML is escaped, tested); subjects carry
      people's text, so control characters are flattened before the mailer sees them (tested with CR/LF).

## Findings

| # | Severity | Category | Where | What was tried | Status |
|---|---|---|---|---|---|
| 1 | Low | A03 header | `TemplatedOutbox` subject | An expense description with `\r\nBcc: …` ends up in the PM's email subject | Fixed: control characters become spaces (Symfony also encodes headers); test added |
| 2 | Low | A04 availability | event handlers | An email that cannot be built after the command committed turned the saved action into a 500 | Fixed: `ContainEventFailures` on the event bus logs and swallows handler failures (unit test) |

## Checked, nothing found

- **Recipients:** only active accounts get email; a Team Lead only hears about their own expenses; a PM only
  about their project; the digest goes to the project's PM and the Admins.
- **The trail is append-only:** written with plain SQL in the same transaction as the change; the ORM maps it
  read-only; no endpoint changes or deletes it.
- **Dashboards are read-only** and compute from the same queries the finance and expense screens use.

## Not applicable

- A07 (no auth change), A08 (no uploads), A10 (no outbound requests besides SMTP through the queue).
