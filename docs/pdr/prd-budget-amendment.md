# Adición al presupuesto: a new stage on an approved budget

## What the user needs

> "While having one Etapa running, I need to be able to add another Etapa, then I need to be able to work in both
> Etapas at the same time. It could be more Etapas."

Confirmed flow: add the stage (with its lines and milestones) → the Admin approves it → "Iniciar etapa" on it → all
of them run at the same time. Any number of new stages, one at a time or several together; each needs the Admin's
approval before it is started, funded or charged; nothing already approved changes; once approved a new stage
behaves exactly like the others (start, deposits, "Asignar del fondo", expenses, "Finalizar etapa" in any order).

## The problem

Once the Admin approves the budget it never changes (SPEC § 10, `Budget::assertEditable()`). "Agregar etapa" is
only offered while the budget is a draft (`PlanPresenter` → `permissions.edit`, `PlanBoard.tsx`), and
`POST /projects/{id}/stages` answers 409 `budget_locked` after approval (`AddStageHandler`). Stages can already run
in parallel and the project fund can pay for one, but a running project has no way to get **a new stage**.

## The idea

One new concept: an **Adición al presupuesto** (budget amendment). On an approved budget the PM proposes only what
is new (stages with their lines, milestones and dates), the Admin approves it, and everything already approved
stays locked. No new screen and almost no new button: the workflow the user already knows.

| Who | Does | How |
|---|---|---|
| PM or Admin | Adds a stage after approval | "Agregar etapa" is back on the approved plan; the stage lands in the open amendment (created on the spot) with the badge "Adición 1 · Borrador" |
| PM or Admin | Fills and sends it | Partidas, hitos and dates as today, only on that stage; the budget card's button reads "Enviar adición" |
| Admin | Reviews it | "Aprobar adición" or "Devolver" with a comment, in the same place as the budget's own buttons |

## Decisions for the user

None. The Spanish name is "Adición" (the term for contract additions in Colombian construction).

## Rules

- **Model.** `BudgetAmendment` (number, status, events) hangs off the budget; `stage.amendment_id` is null for every
  stage of the original budget. Lines and milestones follow their stage, so approved rows need no marker: a row is
  editable only when its stage's amendment is editable, and the original rows keep answering 409 as today.
- **Several stages per amendment, one open amendment at a time.** One approval round for everything added now; the
  next amendment opens once the first is approved. Deleting the last stage of a draft amendment removes it.
- **Same states, events and emails.** It reuses `BudgetStatus` (DRAFT → SUBMITTED → APPROVED, or RETURNED with the
  Admin's comment), `BudgetEvent` (with a nullable `amendment_id`) and the budget's domain events, which gain the
  amendment number so the `budget_submitted` / `budget_reviewed` emails say "Adición 1". Approving an amendment
  does not change the project's status.
- **Who.** PM or Admin draft and send; only the Admin approves or returns, as for the budget.
- **Editable while an amendment is open:** its own stages (name, dates, lines, milestones, delete). Not the original
  stages (names excepted, as today), not the contingency, not the order (new stages go last). Categories can be
  added as today. While it is submitted, nobody edits it and no stage can be added.
- **Before sending:** the budget's rules, on the amendment's stages only (a line in every stage, weights add up to
  100 %), listed under "Para enviar la adición falta:".
- **A stage not yet approved exists only in the plan tab.** `DoctrinePlanDirectory`, the one query the other
  contexts read, leaves it out, so deposits, fund assignments, contingency draws, expenses, Fondos, the dashboards
  and the daily digest never see it. In the plan tab it shows its badge and "Pendiente de aprobación", cannot be
  started and its milestones cannot be met (409 `stage_not_approved`); it is out of "Total etapas", the category
  totals and the progress. Team Leads see it like any other stage (no money, no buttons).
- **Once approved** it is a stage like the others: "Total etapas" and "Presupuesto total" grow, every stage's share
  is recomputed, BAC and "Depositado de X presupuestados" include it, the project's progress drops (a 0 % stage
  now weighs) and "Iniciar etapa" appears. The contingency never changes.
- **History.** The budget card keeps "Aprobado" and lists the amendments under it: "Adición 1 · Acabados exteriores
  · $ 2.000.000 · Aprobada el 12/10/2026". The open one shows its status, the returned note and the buttons.
- **Existing projects.** Nothing is rewritten: every existing stage belongs to the original budget.

## Where it lives

**Backend (Planning context).** One migration: table `budget_amendment` (budget_id, number, status, created_at,
approved_at), nullable `stage.amendment_id` and `budget_event.amendment_id`.
- `Budget::amendmentToEdit()`, `Budget::assertStageEditable(Stage)`, and `submit()/approve()/returnToDraft()`
  acting on the open amendment once approved; `Stage::isPendingApproval()`; `BudgetAmendment implements Audited`.
- Handlers: `AddStageHandler` routes to the amendment; every stage, line and milestone edit (`UpdateStage`,
  `DeleteStage`, `AddLine`, `ChangeLine`, `DeleteLine`, `AddMilestone`, `UpdateMilestone`, `DeleteMilestone`) uses
  `assertStageEditable($stage)`; `StartStageHandler` and `CompleteMilestoneHandler` refuse a pending stage;
  `DeleteStageHandler` drops an emptied amendment; `DoctrinePlanDirectory` filters pending stages in `stages()`,
  `categories()`, `schedule()`, `overdueMilestones()` and `progress()`: the one port Finance (`PlanningFundedPlan`),
  Expense (`PlanningExpensePlan`) and Reporting (`PlanningPlanSource`) read, so a deposit, an assignment or an
  expense to a pending stage answers 422 like a stage of another project.
- `NotifyBudgetSubmitted` / `NotifyBudgetReviewed` pass the amendment number to the email templates.
- No new endpoint: `POST /projects/{id}/stages` and `POST /projects/{id}/budget/{submit|approve|return}` act on the
  open amendment once the budget is approved.
- `PlanPresenter`: totals, shares, category budgets and progress over approved stages only; `StageOutput.amendment`, `.approved`, `.editable`, `.weight` (null while pending);
  `BudgetOutput.amendments`; `permissions.addStage`; `issues` for the open amendment. Then `make api`.

**Frontend.** No new slice; the existing kit and colours.
- `widgets/plan-board`: `PlanBoard` uses `permissions.addStage`; `StageCard` shows the badge and gates its buttons;
  `BudgetCard` lists the amendments.
- `features/plan-stages`: a hint in the stage form after approval ("Se agrega como adición: el administrador debe
  aprobarla antes de iniciarla."). `features/plan-review`: "Enviar adición" / "Aprobar adición". Strings under
  `plan.amendment.*`.

## Build order (one branch, test-first per commit)

1. **Domain and migration:** amendments open only on an approved budget, one at a time, their workflow and events,
   original stages still locked, an emptied amendment goes away.
2. **API:** the round trip (add after approval → figures unchanged, start and milestone 409, submit, return, fix,
   approve → totals, shares, "Iniciar etapa"); a pending stage refused by deposits and expenses; BAC before and
   after; the email subject names the amendment.
3. **UI:** the badge, the list on the budget card, no "Iniciar etapa" while pending, the buttons' labels.
4. **Docs:** README, SPEC, help topics, regression cases and their smoke tests.

Not split: one context, one screen; the other contexts change through a single filter. Build agent: `opus`.

## Verification

`make test` and `make gate` in Docker after every commit. New cases in `docs/tests/ui-regression.md` § 6:

| Case | What | How |
|---|---|---|
| PLN-09 | Add a stage on the approved Torre Norte: "Adición 1 · Borrador", figures unchanged, no "Iniciar etapa"; absent from Fondos and the expense form | Smoke |
| PLN-10 | Send, the Admin returns it with a comment, the PM fixes a line and sends again, the Admin approves: totals, shares, "Iniciar etapa", the Fondos row, two emails in Mailpit | Manual |
| PLN-11 | The new stage runs in parallel: Iniciar etapa while another stage runs; deposit to it, Asignar del fondo, an expense; close either one first | Manual |
| PLN-12 | Delete the only stage of a draft amendment: the card goes back to a plain "Aprobado"; while one is submitted, everything is locked and a Team Lead sees the badge and no money | Manual |

## SPEC, README and help to update

SPEC § 2 (drafting and approving an amendment), § 3.2, § 3.4 and § 10 (an approved budget grows only by amendment;
what a pending stage can and cannot do), the emails sections. README: the stage and budget API rows, the data-model
bullet. Help: "enviar-presupuesto" and "aprobar-presupuesto" get an "Adiciones" section; "avance-etapas" says a
stage starts once approved; in "categorias-y-etapas" the stale note ("El orden importa: al finalizar una etapa, el
saldo que le sobre pasa a la siguiente.") becomes "Varias etapas pueden ir en paralelo; el saldo de una etapa
finalizada pasa al fondo del proyecto o a la etapa que elija el administrador."

## Left out on purpose

- No changes to an approved stage (more lines, a bigger contingency): only new stages. Overruns stay flagged.
- No removal or reordering of approved stages.
- No separate amendment screen or endpoints: the budget card and its three endpoints carry it.
