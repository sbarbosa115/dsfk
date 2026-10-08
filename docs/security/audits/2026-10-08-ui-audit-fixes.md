# Security audit — ui-audit-fixes — 2026-10-08

- **Branch:** `feature/ui-audit-fixes` at `ed38737`, against `origin/main`
- **Scope:** frontend only. No route, controller, input DTO, upload, email, job or dependency was added or changed.
  What an attacker can reach that changed: `RowActions` renders links from `href`/`to` given by the tables (attachment
  URLs built by `attachmentUrl(id)`, app routes); `title` tooltips now carry user text (a project's description and
  name, a disabled action's reason); Auditoría shows field names through translations instead of raw keys. Backend:
  one new test (`AuditFieldNamesTest`), no production PHP.
- **Tools:**
  - `composer audit`: clean
  - `npm audit --omit=dev`: clean
  - secrets in the diff: 1 lead, a false positive: `password: 'Contraseña'` is the Spanish label of the audited `password` field (its values are masked as `***` by `AuditTrail::HIDDEN`)

## Leads from audit.py

Each one checked against the checklist in `docs/security/README.md` and resolved into a finding or "nothing found".

- [x] [A03 XSS] `RowActions.tsx` main button `href`: passes `safeHref`, which allows only `http(s):` and same-origin paths; `javascript:` renders a button, not a link (test "opens a file in a new tab from the menu, and never a script"). See finding 1 for `/\host`.
- [x] [A03 XSS] `RowActions.tsx` menu item `href`: same check as above.

## Findings

| # | Severity | Category | Where | What an attacker could do | Status |
|---|---|---|---|---|---|
| 1 | Low | A01 open redirect | `shared/ui/RowActions.tsx` `safeHref` | A row action given `/\\evil.test` (which browsers read as `//evil.test`) would link to another site. No caller passes user data today (hrefs come from `attachmentUrl(id)`), so not exploitable now; hardened so the component stays safe for future callers. | Fixed: `safeHref` rejects `//` and `/\\`; test "never links to another site through a path the browser reads as one" |

## Checked, nothing found

- **A03 Injection / XSS:** no `dangerouslySetInnerHTML`, no HTML strings; every new text (descriptions in `title`,
  translated field names, row names in `aria-label`) goes through React's escaping. `<script>` in a project
  description renders as text in the list, the header and the tooltip.
- **A01 Access control:** no permission logic moved to the client. Row menus only offer what the API's
  `permissions` say (Gastos, Movimientos, Plan); the user on/off toggle keeps its rules (no self-disable, a super
  admin locked for plain admins) and the API still refuses either way (`UserApiTest`).
- **A04 Insecure design (confirmation):** risky actions still ask: void and reject open their modal with a reason;
  disabling a user still opens the confirmation (`UserList.test`). Nothing destructive runs from one click in a menu.
- **A05 Misconfiguration:** no config, header, CSP or firewall change.
- **A06 Vulnerable components:** no dependency added; `composer audit` and `npm audit --omit=dev` clean.
- **A09 Logging:** the audit log keeps its data; only its display changed. `password` stays masked.
- **Impersonation ("Ver como"):** untouched (`features/impersonate` not changed).

## Not applicable

- A02 Cryptographic failures, A07 Authentication, A08 Integrity, A10 SSRF: no change touches them (no auth, crypto, deserialisation or outbound calls).
