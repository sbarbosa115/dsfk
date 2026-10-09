# Project (`/projects/:id`)

**Purpose:** Everything about one project: its plan and budget, monitoring, expenses, funds, caja menor and team.
**Roles:**
- Admin: every tab and action, including approving/returning the budget, deposits, contingency, completing stages,
  voiding, signing off cycles, reopening milestones, editing the project and its members.
- PM (project member): tabs as Admin; drafts the budget and submits it, adds categories/stages/lines/milestones,
  starts stages and marks milestones, records expenses, approves/rejects Team Lead expenses (above the limit the
  PM's approval only makes it "Espera al administrador"), reimburses, closes caja menor cycles. Cannot approve/return
  the budget, deposit, draw contingency, complete stages, void, sign off, reopen milestones, edit the project or
  members.
- Team Lead: tabs "Presupuesto y plan" (stages and milestones, no money), "Gastos" (only their own; records
  out-of-pocket expenses, corrects pending/rejected ones, attaches receipts), "Resumen". Links to other tabs fall
  back to the plan tab.
**Last updated:** 2026-10-07 (run `issues/runs/2026-10-07-admin-ui.md`)

Demo data: "Torre Norte" (Activo, budget approved, stage 1 Finalizada, stage 2 En curso, deposits, expenses in every
status, one signed-off cycle), "Casa Campestre" (budget Enviado a aprobación), "Bodega Sur" (Borrador, empty).

## Page frame

Shows: "← Volver a proyectos", the project name and description, "Editar proyecto" (Admin; opens the project modal,
see `portfolio.md`), and the tabs: Presupuesto y plan · Tablero · Gastos · Fondos · Caja menor · Resumen. The tab is
kept in the URL (`?tab=plan|dashboard|expenses|finance|petty-cash|overview`); default `plan`.
States: loading: "Cargando…" · error/not a member/unknown id: "← Volver a proyectos" + "Este proyecto no existe o no
participas en él." Each tab loads its own data with its own loading/error ("Reintentar") state.

All modals have "Cancelar" and a submit button; the API's field errors show under the fields, other refusals in a
red alert at the top of the modal (Spanish texts in `es.ts` → `errors`).

## Views

### Presupuesto y plan · `?tab=plan`

Intro: "El plan de la obra: etapas con sus partidas y sus hitos, y el presupuesto que el administrador aprueba."
+ "Agregar etapa" (while editable).

**Budget card** ("Presupuesto" + status badge): "Total etapas", "Contingencia" (pencil "Editar contingencia" while
editable), "Presupuesto total", "Avance" (%). Notes: Approved → "Presupuesto aprobado el <date>. Ya no se puede
modificar: solo nombres de etapas y nuevas categorías."; Submitted → "El presupuesto espera la aprobación del
administrador…"; Returned → "Devuelto por <user>: “<comment>”". While submittable and incomplete: "Para enviar el
presupuesto falta:" with "Agregar al menos una etapa.", "La etapa “X” no tiene partidas.", "Los hitos de la etapa
“X” deben sumar 100 % (hoy suman N)."

**Categorías** card: intro, "Nueva categoría" input + "Agregar", table "Categoría" / "Presupuestado" with pencil
"Renombrar categoría" and "Quitar".

**One card per stage**: "N. <name>" + stage status badge, planned dates (+ "Iniciada el…", "Finalizada el…"), stage
total and "X% del presupuesto", progress bar; stage actions; "Partidas" table (Categoría, Descripción, Cantidad,
Valor unitario, Total; pencil "Editar partida", "Quitar") with "Agregar partida"; "Hitos" with "Suma de pesos: N"
(red when ≠ 100 %) and "Agregar hito" (disabled at 100 %), table (Nombre del hito, Peso, Fecha planeada, Cumplido)
whose rows are tinted "Cumplido" or "Atrasado".
Empty: "Aún no hay etapas. Agrega la primera para empezar a armar el presupuesto." · "Esta etapa aún no tiene
partidas." · "Esta etapa aún no tiene hitos." · no categories: "Agregar partida" disabled and "Crea al menos una
categoría antes de agregar partidas."

| Action (button) | When available (Admin) | Modal → result |
|---|---|---|
| Agregar etapa | budget DRAFT/RETURNED | "Agregar etapa": Nombre de la etapa, Inicio/Fin planeado (opc.) → `POST /projects/{id}/stages` |
| Editar etapa (pencil) | DRAFT/RETURNED (all fields) or APPROVED (name only; dates disabled, hint "Las fechas planeadas quedaron fijas…") | "Editar etapa" → `PATCH /stages/{id}` |
| Subir / Bajar (arrows) | DRAFT/RETURNED | no modal → `PUT /projects/{id}/stages/order` |
| Eliminar etapa | DRAFT/RETURNED | confirm "Se eliminará la etapa “X” con sus partidas e hitos." → `DELETE /stages/{id}` |
| Iniciar etapa | APPROVED, stage Pendiente | "Iniciar etapa · X": Fecha de inicio real (today, not future) → `POST /stages/{id}/start` → En curso |
| Agregar / Renombrar / Quitar categoría | always (also after approval) | inline add; "Renombrar categoría" modal → `PATCH /categories/{id}`; "Quitar" confirm "Se eliminará la categoría “X”." → `DELETE`, refused if used (`category_in_use`) |
| Agregar partida | DRAFT/RETURNED, ≥ 1 category | "Agregar partida · <stage>": Categoría, Descripción, Unidad, Cantidad (decimal comma, ≤ 3 decimals), Valor unitario; hint shows "Total de la partida: …" → `POST /stages/{id}/lines` |
| Editar partida / Quitar | DRAFT/RETURNED | "Editar partida" → `PUT /budget-lines/{id}`; confirm "Eliminar partida" ("Se eliminará la partida “X”.") → `DELETE` |
| Agregar hito | DRAFT/RETURNED, weights < 100 % | "Agregar hito · <stage>": Nombre del hito, Peso en la etapa (%) (pre-filled with what is missing), Fecha planeada (opc.) → `POST /stages/{id}/milestones` |
| Editar hito / Quitar | DRAFT/RETURNED | "Editar hito" → `PATCH /milestones/{id}`; confirm "Eliminar hito" → `DELETE` |
| Marcar cumplido | APPROVED, milestone not met | "Hito cumplido · X": Fecha de cumplimiento (not future), Notas (opc.) → `POST /milestones/{id}/complete` |
| Reabrir | Admin only, APPROVED, milestone met, stage not Finalizada | no modal → `POST /milestones/{id}/reopen` |
| Editar contingencia (pencil) | DRAFT/RETURNED | "Editar contingencia": Contingencia (money) → `PUT /projects/{id}/budget/contingency` |
| Enviar a aprobación | DRAFT/RETURNED; disabled while the issue list is not empty | confirm "Después de enviarlo no podrás modificarlo…" → `POST …/budget/submit` → SUBMITTED |
| Aprobar presupuesto | Admin, SUBMITTED | confirm "Una vez aprobado, el presupuesto queda bloqueado… El proyecto pasa a Activo." → `POST …/budget/approve` → APPROVED (project DRAFT → ACTIVE) |
| Devolver | Admin, SUBMITTED | "Devolver": Qué debe cambiar el gerente (required) → `POST …/budget/return` → RETURNED |

Budget statuses: `DRAFT` Borrador → `SUBMITTED` Enviado a aprobación → `APPROVED` Aprobado, or `SUBMITTED →
RETURNED` Devuelto con observaciones → (edit) → `SUBMITTED`. While SUBMITTED nothing but categories can change.
Stage statuses: `PENDING` Pendiente → `IN_PROGRESS` En curso (Iniciar etapa) → `COMPLETED` Finalizada (Fondos →
Finalizar etapa).
PM: same, minus Aprobar/Devolver and Reabrir. Team Lead: intro "Las etapas de la obra y sus hitos…", no amounts, no
lines, no actions; Categorías shown only if any exist.

### Tablero · `?tab=dashboard` (Admin, PM)

Read-only. Intro "Si el gasto va de acuerdo con el avance…"; before approval an info "Los indicadores se llenan
cuando el presupuesto está aprobado…". Stats: "Gastado" (X% de budget), "Avance" (+ "Según el plan debería ir en
X%"), "Indicadores" (CPI (costo), SPI (plazo) badges, tooltips explain them), "Costo final estimado" (or "Sin datos"
+ "Presupuesto ÷ CPI: aparece cuando hay avance y gasto."; with data "Diferencia con el presupuesto: …"). "Lo que
necesita atención": alerts (stage over/near budget, stage late, overdue milestones, expenses pending/to reimburse,
unsigned cycles) or "Nada por ahora.". Charts "Avance y gasto por etapa" (only with stages) and "Dinero por mes",
each with "Ver como tabla"/"Ver gráfica". Table "Etapas": planned/actual dates, progress (+ "planeado X%"),
executed %, CPI; "Atrasada" under late stages.

### Gastos · `?tab=expenses`

Intro (manager text) + buttons "Reembolsar" (secondary; when "Por reembolsar" > 0) and "Registrar gasto" — both only
once the budget is approved; before that an info "Los gastos se registran cuando el administrador aprueba el
presupuesto." Stats: "Por aprobar" (count + total), "Por reembolsar", "Límite de líder de equipo" ("Por encima, el
administrador también aprueba."). Filters: search "Descripción, proveedor o factura…", "Estado", "Etapa". Table:
Fecha, Gasto (+ supplier · invoice, "Rechazado: “reason”", "Sin factura o recibo"), Etapa · categoría, Pagado
("Fondos de la etapa", "Caja menor", "Pagado por <name>"), Monto; rows tinted by status.
Empty: "Aún no hay gastos." / with filters "Ningún gasto coincide con los filtros." + "Ver todos".

| Action (button) | When (Admin) | Modal → result |
|---|---|---|
| Registrar gasto | budget APPROVED | "Registrar gasto": Descripción, Etapa (open stages), Categoría, Fecha, Monto, Se paga desde (Fondos de la etapa / Caja menor), Proveedor (opc.), Número de factura (opc.); submit "Registrar" → `POST /projects/{id}/expenses` → APPROVED at once; money leaves the stage or caja menor (`insufficient_funds` if not enough) |
| Ver gasto (eye) | any row | detail modal: status, amount, date, stage, category, paid, supplier, invoice, rejection reason, reimbursement; "Facturas y recibos"; "Historial" (events, "Antes: …" for corrections) |
| Aprobar (check icon) | status Pendiente, or Espera al administrador | confirm "Aprobar gasto": "“X” de <name> por <amount>." → `POST /expenses/{id}/approve` → APPROVED; refused without receipt (`receipt_required`) |
| Rechazar (ban icon) | same as Aprobar | "Rechazar “X”": Motivo del rechazo (required) → `POST /expenses/{id}/reject` → REJECTED |
| Adjuntar recibo | not Anulado, and (no receipt or status Pendiente/Espera/Rechazado) | "Adjuntar recibo · X": file (PDF/JPG/PNG/WEBP/HEIC ≤ 10 MB); submit "Adjuntar" → `POST /expenses/{id}/attachments` |
| Recibo (link) | row with a receipt | opens the first file in a new tab |
| Anular | Admin, status Aprobado and not reimbursed | "Anular gasto" with warning "…Deja de contar en el presupuesto y el dinero vuelve a donde salió."; Motivo de la anulación (required) → `POST /expenses/{id}/void` → VOIDED |
| Reembolsar | ≥ 1 approved Team Lead expense | "Reembolsar": checkboxes "<name> · <desc> · <date> · <amount>", Fecha, Medio de pago, Referencia (opc.); submit "Reembolsar $ <total>" → `POST /projects/{id}/reimbursements` → REIMBURSED, one caja menor movement |

Expense statuses: `SUBMITTED` Pendiente · `PM_APPROVED` Espera al administrador · `APPROVED` Aprobado · `REJECTED`
Rechazado · `REIMBURSED` Reembolsado · `VOIDED` Anulado.
- Admin/PM expense (stage or caja menor): APPROVED on creation.
- Team Lead (out of pocket): SUBMITTED → (PM ≤ limit, or Admin any amount) APPROVED → REIMBURSED; PM above limit →
  PM_APPROVED → (Admin) APPROVED. SUBMITTED/PM_APPROVED → REJECTED (Admin; PM only from SUBMITTED) → Team Lead
  corrects ("Corregir gasto", "Guardar y enviar de nuevo") → SUBMITTED. APPROVED (not reimbursed) → VOIDED (Admin).
- "Corregir gasto" (pencil) appears only to the person who paid, on SUBMITTED/REJECTED: never for an Admin's rows.
PM: same minus Anular. Team Lead: own expenses only; "Registrar gasto" without "Se paga desde" (always out of
pocket).

### Fondos · `?tab=finance` (Admin, PM)

Intro + (Admin, budget approved) "Usar contingencia" (only when the contingency balance > 0) and "Registrar
depósito". Before approval: info "Los depósitos se registran cuando el administrador aprueba el presupuesto." Stats:
"Depositado" (de X presupuestados), "Gastado" (Disponible en etapas), "Caja menor" (Depositado), "Contingencia"
(Presupuestada · usada). "Fondos por etapa": Etapa (+ "X pasaron a la siguiente"), Presupuesto (+ "Gastado: X
(N%)"), Recibido (tooltip with deposited / contingency / carried in; "X por encima del presupuesto"), Financiado
(bar), Disponible, and "Finalizar etapa" on En curso rows (Admin). "Por categoría": Presupuesto, Gastado, Ejecutado.
"Movimientos": search "Referencia o nota…", table Fecha, Movimiento (type + method · reference), Destino (entries,
note, "Anulado por X el D: “reason”"), Monto; voided rows greyed. Empty: "Aún no hay movimientos de dinero." /
"Ningún movimiento coincide con la búsqueda."

| Action (button) | When (Admin) | Modal → result |
|---|---|---|
| Registrar depósito | budget APPROVED | "Registrar depósito" (wide): Fecha (not future), Medio de pago (Transferencia/Efectivo/Cheque/Otro), Referencia (opc.), "Distribución": rows of Destino N (Etapa: X for open stages / Caja menor / Contingencia), Categoría N (opc., stages only), Monto N, "Quitar"; "Agregar destino"; "Total del depósito: …"; Nota (opc.); submit "Registrar" → `POST /projects/{id}/deposits` |
| Usar contingencia | APPROVED, contingency balance > 0 | "Usar contingencia": Etapa (open), Monto (hint "Disponible en la contingencia: …"), Fecha, Motivo (required); submit "Pasar a la etapa" → `POST …/contingency/draws` |
| Finalizar etapa | APPROVED, stage En curso | "Finalizar “X”": info where the balance goes (next open stage, or contingency if last, or "no tiene saldo") + "Una etapa finalizada no recibe más dinero."; Fecha de finalización → `POST /stages/{id}/complete` → COMPLETED + CARRYOVER movement; refused if milestones pending |
| Adjuntar (deposit row) | non-voided deposit | "Adjuntar comprobante": Archivo → `POST /movements/{id}/attachments`; then a "Comprobante" link |
| Anular (ban icon) | non-voided deposit or contingency draw | "Anular movimiento": warning "<type> del <date> por <amount> (<destinations>). Seguirá en la lista como anulado…"; Motivo de la anulación → `POST /movements/{id}/void` |

Movement types: Depósito, Uso de contingencia, Saldo trasladado (carry-over; never voidable). PM: read-only.

### Caja menor · `?tab=petty-cash` (Admin, PM)

Intro + "Cerrar ciclo" (Admin and PM). Stats: "Saldo", "Entradas" (+ "Saldo inicial del ciclo"), "Gastos" (+
"Reembolsos"), "Ciclos por firmar". Card "Ciclo N (actual)" ("Abierto el …") with its movements (Fecha, Movimiento,
Registró, Monto; receipt/proof links) or "Este ciclo aún no tiene movimientos." Card "Ciclos anteriores": #, Cerrado
(date + by), Saldo inicial, Entradas, Salidas, Saldo final; eye "Ver el ciclo N"; "Firmar" on CLOSED rows (Admin).
Empty: "Aún no se ha cerrado ningún ciclo."

| Action (button) | When (Admin) | Modal → result |
|---|---|---|
| Cerrar ciclo | always | "Cerrar el ciclo N": info "El ciclo se cierra con un saldo de X; ese saldo abre el siguiente. Sus movimientos ya no se podrán anular."; Nota de cierre (opc.) → `POST /projects/{id}/petty-cash/close` → CLOSED, next cycle opens |
| Ver el ciclo N (eye) | history row | "Ciclo N" detail: status, balances, closed/signed by, note, movements |
| Firmar | Admin, cycle Cerrado, por firmar | confirm "Firmar el ciclo N": "Revisaste los movimientos del ciclo: empezó con X y cerró con Y." → `POST /petty-cash-cycles/{id}/sign-off` → SIGNED_OFF |

Cycle statuses: `OPEN` Abierto → `CLOSED` Cerrado, por firmar → `SIGNED_OFF` Firmado. Unsigned cycles do not block
deposits to the caja menor.

### Resumen · `?tab=overview`

"Datos del proyecto": Estado, Moneda, Inicio/Fin planeado, Mi rol. "Equipo del proyecto": intro, (Admin) toolbar
Persona (active non-admin users not yet members) + Rol (Gerente de proyecto / Líder de equipo) + "Agregar"; table
Persona, Correo, Rol (Admin: inline role select) and "Quitar". Empty: "Aún no hay nadie en este proyecto."

| Action | When (Admin) | Result |
|---|---|---|
| Agregar | a person chosen | `POST /projects/{id}/members`; green "<name> ahora es <role> del proyecto."; refused if a PM already exists ("El proyecto ya tiene un gerente…") |
| Change role (select) | member row | same endpoint; same messages |
| Quitar | member row | confirm "Quitar": "<name> dejará de ver este proyecto. Sus gastos y registros se conservan." → `DELETE /projects/{id}/members/{memberId}` |

PM / Team Lead: read-only list.

## Flows

### Plan → approval (Bodega Sur, Admin)
1. Categorías: add "Materiales" → listed. 2. "Agregar etapa" "Obra" → stage card. 3. "Agregar partida" → line and
stage total. 4. "Agregar hito" 100 % → "Suma de pesos: 100%". 5. Issue list disappears, "Enviar a aprobación" enabled
→ confirm → "Enviado a aprobación", edit buttons gone. 6. "Aprobar presupuesto" → "Aprobado", project Activo,
"Iniciar etapa" and "Marcar cumplido" appear.

### Money (Torre Norte, Admin)
1. Fondos → "Registrar depósito" split stage + caja menor → movement listed, stats grow. 2. Gastos → "Registrar
gasto" from Caja menor → Aprobado; Caja menor balance drops. 3. Gastos → approve "Cinta y señalización" (Pendiente)
→ Aprobado; "Reembolsar" it → Reembolsado. 4. Caja menor → "Cerrar ciclo" → listed "Cerrado, por firmar" → "Firmar".

## Rules

- Validation: stage name ≤ 150, planned end ≥ start; line description ≤ 255, unit ≤ 20, quantity > 0 with ≤ 3
  decimals, unit price positive; milestone name ≤ 200, weight 0.01–100 % with ≤ 2 decimals, stage total ≤ 100 %;
  category name ≤ 100 and unique in the project; return comment required ≤ 2000; start/complete/met dates not in
  the future, stage end not before its start; expense description ≤ 255, supplier ≤ 150, invoice ≤ 60, amount
  positive; reasons (reject/void) required ≤ 2000; deposit: 1–30 allocations, each amount positive, completed stages
  refused; draw ≤ contingency balance; files PDF/JPG/PNG/WEBP/HEIC ≤ 10 MB checked by content.
- Business: submit/approve need every stage with ≥ 1 line and milestones = 100 %; expenses only after approval;
  paying needs enough money (`insufficient_funds`); completed stages cannot be charged to stage funds; voiding a
  movement is refused if it would overdraw (`void_would_overdraw`) or its caja menor cycle is closed (`cycle_closed`).
- Access: Team Leads get no money figures, only their own expenses/receipts; non-members get 404.

## Side effects

- Emails (Messenger queue, Mailpit locally): budget submitted (Admins), returned/approved (PM), TL expense submitted
  (PM), PM-approved above limit (Admins), rejected/reimbursed (Team Lead), cycle closed (Admins), spending
  thresholds and low caja menor (Admins + PM). Daily digest `app:alerts:daily`.
- Every change is audited (see `admin.md`).

## Unclear

- Spec §2 matrix says only the PM reimburses and closes caja menor cycles; §12 and the code let the Admin do both.
  Which is intended?
- Spec §2: above the limit "PM first, then Admin". The code lets an Admin approve a SUBMITTED over-limit expense
  directly, and the Admin's approve dialog then still says "después de tu aprobación, el administrador también debe
  aprobarlo". Should the Admin skip the PM step, and should that text show to an Admin?
- Expense date has no "not in the future" check (deposits, draws and milestone dates do). Intended?
- Spec §4: Team Lead limit, low-balance % and budget thresholds have a "project override"; there is no UI or API
  field for it.
- Categories can be added/renamed while the budget is SUBMITTED (spec §10 only says "after approval"). Intended?

## Sources

- Page: `backend/assets/react/pages/project-detail/ui/ProjectDetailPage.tsx`
- Plan: `widgets/plan-board/ui/{PlanBoard,BudgetCard,StageCard}.tsx`, `features/plan-categories`, `plan-stages`,
  `plan-lines`, `plan-milestones`, `plan-review` → API `backend/src/Planning/UI/Http/Controller/*` (permissions
  `Planning/UI/Http/Presenter/PlanPresenter.php`, rules `Planning/Domain/Model/{Budget,Stage,Milestone}.php`).
- Dashboard: `widgets/project-dashboard/ui/{ProjectDashboard,charts}.tsx` → `GET /api/projects/{id}/dashboard`
  (`backend/src/Reporting/UI/Http/Controller/DashboardController.php`).
- Expenses: `widgets/expense-board/ui/{ExpenseBoard,ExpenseDetailModal}.tsx`, `features/expense-form`,
  `expense-review`, `expense-receipt`, `expense-void`, `expense-reimburse` → `backend/src/Expense/UI/Http/Controller/ExpenseController.php`
  (permissions `Expense/UI/Http/Presenter/ExpensePresenter.php`, rules `Expense/Domain/Model/Expense.php`).
- Funds: `widgets/finance-board/ui/{FinanceBoard,MovementList}.tsx`, `features/finance-deposit`, `finance-draw`,
  `movement-void`, `movement-proof`, `stage-complete` → `backend/src/Finance/UI/Http/Controller/{FinanceController,MovementController}.php`.
- Caja menor: `widgets/petty-cash-board/ui/PettyCashBoard.tsx`, `features/cycle-close`, `cycle-signoff` →
  `backend/src/Finance/UI/Http/Controller/PettyCashController.php`.
- Members: `features/project-members/ui/MembersPanel.tsx` → `POST|DELETE /api/projects/{id}/members` (`ProjectController.php`).
- Files: `GET /api/attachments/{id}` → `backend/src/Document/UI/Http/Controller/AttachmentController.php`.
- Demo data: `backend/src/DataFixtures/DemoFixtures.php`.
