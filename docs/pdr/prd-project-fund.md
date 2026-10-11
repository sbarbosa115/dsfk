# Fondo del proyecto: money not yet assigned, and stages that close in any order

## The problem

Stages can already run at the same time. The money is what breaks:

- When a stage closes, its leftover goes to the **next stage by position**, or to the **contingency** if it was the
  last one. With parallel stages that is a guess: closing stage 1 silently feeds stage 2, and closing the last
  stage while others are still open dumps the money into the contingency, where it looks like overrun cover.
- A deposit has **nowhere neutral to sit**. Every peso must go to a stage, the caja menor or the contingency the
  moment it is recorded.

## The idea

A fourth account per project: **Fondo del proyecto**. It holds money that belongs to the project but has not been
given to a stage yet.

| The Admin wants to… | How |
|---|---|
| Deposit money without deciding the stage yet | Choose "Fondo del proyecto" as the destination (or as one part of the split) |
| Give that money to a stage or the caja menor later | "Asignar del fondo": amount, destination, date, optional note |
| Close a stage | Same dialog as today, plus one select, "Destino del saldo": **Fondo del proyecto** (default) or any open stage |

That is the only new choice in the UI: one destination option, one button, one select.

## Rules

- **Who.** Only the Admin deposits, assigns from the fund and closes stages, as today. The PM sees the fund's
  figures. Team Leads still see no money.
- **Closing a stage.** The leftover goes to the fund unless the Admin picks an open stage. The contingency **never**
  receives leftovers again: it is a budgeted reserve, not a pot. Carry-overs still can't be voided. To undo one,
  assign the money back from the fund.
- **Assigning.** It goes only to an open stage or the caja menor, and is capped at what the fund holds. No reason is
  required, because it is not overrun cover. It can be voided like a contingency draw. Into the caja menor, it
  counts as a top-up of the open cycle.
- **Spending.** No expense is paid from the fund. Money must reach a stage or the caja menor first.
- **Reports.** An assignment is not a deposit: "Depositado" and "Dinero por mes" still count deposits only. A
  stage's "Recibido" now includes what it got from the fund.
- **Existing projects.** Nothing is rewritten. Old leftovers already in the contingency stay there. Every project
  starts with a fund of $ 0.

## Where it lives

**Backend (Finance context).** No migration: the ledger's `account` column already fits a new value.
- `LedgerAccount::ProjectFund` (`PROJECT_FUND`) and `MovementType::FundAssignment` (`FUND_ASSIGNMENT`).
- `FundMovement::carryOver()` sends `null` to the fund, and `FundMovement::fundAssignment()` is shaped like
  `contingencyDraw()`.
- `POST /stages/{id}/complete` takes an optional `toStageId`. It must be an open stage of the same project, not the
  stage itself, or the API answers 422. Nobody guesses a "next stage" any more, so `nextStage` is removed from
  `StageFundingOutput`.
- `POST /projects/{id}/fund/assignments`: `{destination: STAGE | PETTY_CASH, stageId?, amount, date, note?}`.
- `FinancePresenter` adds the fund's figures (deposited, carried in, assigned, balance) and `permissions.assignFund`.
- `lastPettyCashTopUp` also counts fund assignments. Then run `make api`.

**Frontend.** The kit and colours are the existing ones (see `prd-ui-audit-fixes.md`).
- `finance-deposit`: one more destination option.
- `stage-complete`: the "Destino del saldo" select in the existing modal.
- New `fund-assign`: `AssignFundModal`, a `FormModal` like `DrawModal` without the reason.
- `finance-board`: a "Fondo del proyecto" tile after "Contingencia", and an "Asignar del fondo" button
  (`action="setup"`) next to "Usar contingencia". The button shows only when the fund holds money.
- Spanish strings and the "Depósitos" help topic.

## Build order (one branch, test-first per commit)

1. **Domain:** the new account and movement, carry-over into the fund, assigning, the cap, voiding, and no spending
   from the fund.
2. **Deposits and closing:** a deposit part to the fund; closing with and without `toStageId`; 422 for a completed or
   foreign stage. `testTheLastStagesLeftoverGoesToTheContingency` is rewritten to expect the fund.
3. **Assigning:** to a stage and to the caja menor, the cap, 403 for the PM, voiding, and 409 once the money was
   spent.
4. **UI:** the tile, the button, the modal's default, and what `AssignFundModal` sends.
5. **Docs and demo:** README, SPEC, help topic. One Torre Norte deposit gets a fund part so the demo shows the tile.

Not split into parallel items: it is one context and one screen, and every step touches `FundMovement` or the board.

## Verification

`make test` and `make gate` in Docker after every commit. New cases in `docs/tests/ui-regression.md` § 7:

| Case | What | How |
|---|---|---|
| FIN-09 | Deposit with a part to the fund | Smoke (Playwright) |
| FIN-10 | Assign from the fund to a stage; its "Disponible" rises | Smoke |
| FIN-11 | Close a stage while two are open; the dialog defaults to the fund; pick the other stage | Manual |
| FIN-12 | Void an assignment; the fund gets it back | Manual |
| FIN-13 | Assign to the caja menor; it shows in the open cycle and the low-balance mail | Manual (Mailpit) |

FIN-07's expected result changes: the leftover goes to the fund unless a stage is chosen.

## SPEC and README to update

SPEC § 2 (permission row), § 3.2 and § 3.8 (stages may overlap; the leftover goes to the fund or a chosen stage),
§ 3.6 (fourth destination), § 3.10 (an assignment is a top-up), § 11 (accounts, deposits, voiding, assignments,
closing). README: the API rows and the ledger bullet.

## Left out on purpose

- No category earmark on an assignment.
- No moving money by hand from a stage to the fund, from the fund to the contingency, or between two stages.
  Assigning from the fund covers these.
- Old leftovers in the contingency are not migrated.

## Timeline

Recorded with `timeline.py`, from 2026-10-11 08:08 to 2026-10-11 08:55. Active time leaves out the pauses.

| Step | Started | Active | Wall clock |
|---|---|---|---|
| Plan | 2026-10-11 08:08 | 8m | 18m |
| Branch and stack | 2026-10-11 08:26 | 1m | 1m |
| Build test-first | 2026-10-11 08:28 | 8m | 8m |
| Gate | 2026-10-11 08:35 | 1m | 1m |
| Security audit | 2026-10-11 08:36 | 1m | 1m |
| Regression run (smoke, then manual) | 2026-10-11 08:37 | 13m | 13m |
| Definition of done | 2026-10-11 08:50 | 1m | 1m |
| Pull request | 2026-10-11 08:51 | 0m | 4m |
| **Total** | | **33m** | **46m** |

Paused for 14m in all.
