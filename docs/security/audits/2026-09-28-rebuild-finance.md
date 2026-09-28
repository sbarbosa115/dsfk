# Security audit — rebuild-finance — 2026-09-28

- **Branch:** `feature/rebuild-finance`, against `feature/rebuild`
- **Scope:** the Finance API (`GET /projects/{id}/finance`, `GET /projects/{id}/movements`,
  `POST /projects/{id}/deposits`, `POST /projects/{id}/contingency/draws`, `POST /movements/{id}/void`,
  `POST /movements/{id}/attachments`, `POST /stages/{id}/complete`); the Document download
  (`GET /attachments/{id}`); uploads and their storage; the Fondos tab.
- **Tools:** `composer audit` clean; `npm audit --omit=dev` clean; secrets grep: 6 lines, all test values or
  labels; `audit.py` compares with `origin/main`, so its access-control and config leads outside Finance and
  Document were cleared in the earlier phases' audits.

## Leads from audit.py

- [x] [A01] every new route: the Finance endpoints resolve the project (from the path, or from the movement or
      stage id) and run `ProjectGuard` first: outside the project → 404 `project_not_found`; Team Lead → 403 on
      the reads; PM → 403 on every write. The download checks `VIEW_FINANCIALS` on the file's project. Tested:
      `testOnlyTheAdminMovesMoneyAndTeamLeadsSeeNoFinances`,
      `testAnotherProjectsPeopleCannotReachThisProjectsMoneyByItsIds` (added in this audit),
      `testAProofOfDepositIsUploadedCheckedAndServedToManagersOnly`.
- [x] [A08 Uploads] `MovementController::attach`: the type is sniffed from the content (`MimeTypes::guessMimeType`,
      never the client's type or name), five types accepted (no SVG, HTML or scripts), 10 MB limit, empty files
      refused, random 128-bit names under `var/uploads/<project>/` (outside `public/`, mode 0640). The download
      sends the stored type with `nosniff`, `Content-Security-Policy: default-src 'none'; sandbox` and an inline
      disposition whose name is URL-encoded by Symfony. Tested: a PHP script named `.pdf` is refused.
- [x] [A03 XSS] `MovementList.tsx` `href={attachmentUrl(a.id)}`: a numeric id in a fixed same-origin path.
- [x] [Frontend] `target="_blank"`: now `rel="noopener noreferrer"`.

## Findings

| # | Severity | Category | Where | What was tried | Status |
|---|---|---|---|---|---|
| 1 | Low | A04 race | draw, void, deposit, stage completion | Two contingency draws (or a draw and a void) sent at once both read the balance before either commits, so together they could take the contingency below zero | Fixed: every money write locks the project row (`SELECT … FOR UPDATE`) for its transaction before reading balances |
| 2 | Low | A03 header/display | `Attachment::accept` | A file name with CR/LF or backslashes | Fixed: control characters, `/` and `\` become `_` in the stored name shown (unit test); the header value is URL-encoded anyway |
| 3 | — | A01 IDOR | deposit, draw | Admin sends money to another project's stage or category by id | Refused: 422 on the allocation's `stageId` / `categoryId` (tests) |

## Checked, nothing found

- **A01 data exposure:** Team Leads get 403 on the summary, the movements and the files; the Fondos tab is not
  offered to them (a `?tab=finance` link falls back to the plan).
- **A04 business rules on the server:** deposits and draws need an approved budget; nothing is dated in the
  future; a completed stage receives no money; a draw never exceeds the contingency; a void never leaves an
  account below zero, only deposits and draws are voided, and never twice; a movement of a closed caja menor cycle
  is final; completing a stage needs every milestone met and settles its money in the same transaction.
- **A03:** no SQL/DQL built from input (the search is a bound, escaped LIKE); amounts are exact decimals
  (brick/money), 13 whole digits at most.
- **Integrity:** movements are never deleted; voids keep who, when and why.

## Accepted

- The file is moved into place before the transaction commits; if the commit then fails, an unreferenced file
  stays in `var/uploads`. It is not reachable (downloads go through the database row) and backups include it.

## Not applicable

- A02 (no new secrets or crypto beyond `random_bytes` names), A07 (no auth change), A10 (no outbound requests).
