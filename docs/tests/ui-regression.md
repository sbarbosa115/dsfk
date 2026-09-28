# UI regression suite

The manual pass through the browser after the automated suites are green and before a release. Each case is
something a real user does, written as steps and what must happen. Run them **in order**: later cases use the
data earlier ones create.

Every new feature adds its cases here, in the section of the screen it lives on, in the same format. A case is
only worth adding if it would catch the feature breaking: name the button, the data and what you should see.
Case IDs are stable: a new case takes the next number in its section, and a removed case leaves a gap.

Each run is recorded as a new file in [`runs/`](runs/) with the result of every ID
(`python3 ~/.claude/skills/symfony-react-app/scripts/new-run.py` creates it).

## Before you start

**Start from known data, not from whatever the last run left.** Everything runs in Docker, on this checkout's
own stack (ports in the root `.env`); the app is the one Symfony serves (`APP_PORT`), built by the `node`
service's Encore watch:

```bash
docker compose exec -u www-data php bin/console doctrine:database:drop --force
docker compose exec -u www-data php bin/console doctrine:database:create
docker compose exec -u www-data php bin/console doctrine:migrations:migrate -n
docker compose exec -u www-data php bin/console app:create-admin admin@demo.test "Administrador" --generate-password
```

(`make seed` replaces the last command once the demo data exists; see the README.)

- `docker compose ps`: every service is up, and `docker compose logs node` ends with "compiled successfully".
- Open Mailpit next to the app: cases that send email end with "an email arrives".
- Use one browser profile for the run and nothing else on the app's origin in it: tabs share one session.
- Dismiss the browser's password manager prompts: they sit on top of the page's buttons.

**Accounts.** Dev only, never reuse these passwords. The admin's password is the one `app:create-admin`
printed; the others are created by the cases below with `demo1234`.

| Role | Sign in with | Lands on |
|---|---|---|
| Super admin | `admin@demo.test` / printed password | `/dashboard` |
| Project Manager | `pm@demo.test` / `demo1234` (created in USR-01) | `/dashboard` once assigned to a project, `/projects` before |
| Team Lead | `lider@demo.test` / `demo1234` (created in USR-01) | `/projects` |

**On every screen, whatever the case says**, also check:

- The browser console has no errors, and nothing is a blank page.
- No text shows a raw translation key or English text.
- Tables follow the house style (the MDX design): no status column, the row colour is the status and a "Color de
  la fila" legend sits above; everything clickable is in the last column, "Acciones"; the primary action sits at
  the end of the filter bar.
- Check new screens in **Oscuro** too (sidebar › Tema).
- A success or error message appears after every save, and it belongs to *that* save.

---

## 1. Authentication

**AUTH-01 · Wrong credentials are refused without saying which one is wrong**
`/login` › email `admin@demo.test`, password `wrong-password` › Ingresar. Then email `nadie@demo.test`, same
password. **Expected:** both times "Correo o contraseña incorrectos." and the form stays.

**AUTH-02 · Ingresar needs both fields**
`/login` with an empty form, then only the email. **Expected:** Ingresar is disabled until both are filled.

**AUTH-03 · Sign in and out**
Sign in as the super admin. **Expected:** the sidebar with "Ver como", the sections Obras (Tablero, Proyectos),
Administración (Usuarios, Configuración, Auditoría) and Ayuda (Documentación), and in its footer the name,
"Super administrador", Tema, "Cambiar contraseña" and "Cerrar sesión". "Cerrar sesión" returns to `/login`;
opening `/users` then goes back to `/login`.

**AUTH-04 · Change your own password**
Sidebar › "Cambiar contraseña" › current `wrong`, new `new-password-1` › Guardar. **Expected:** "La contraseña
actual no es correcta." under the first field. With the right current password: "Contraseña actualizada.";
signing out and in with the new password works.

**AUTH-05 · Repeated failures lock the sign-in for a while**
Run after IMP-02, with `ana@demo.test` (created there): five wrong passwords, then `demo1234`. **Expected:**
"Demasiados intentos. Intenta de nuevo en unos minutos." The lock lasts 15 minutes, so use an account the
rest of the run does not need.

## 2. Users (Admin)

**USR-01 · Create users**
Usuarios › Nuevo usuario › `Laura Gómez`, `pm@demo.test`, `demo1234` › Crear. Again for `Carlos Pérez`,
`lider@demo.test`, `demo1234`. **Expected:** both rows appear green (Activo in the legend), with no admin badge and "Sin proyectos".

**USR-02 · An email belongs to one user, whatever its case**
Nuevo usuario › any name, `PM@demo.test`, `demo1234` › Crear. **Expected:** "Ya existe un usuario con ese
correo." in the dialog; nothing is created.

**USR-03 · Fields are checked one by one**
Nuevo usuario › empty name, `not-an-email`, `short` › Crear. **Expected:** "Revisa los campos marcados." and a
message under each of the three fields.

**USR-04 · Admins cannot lock themselves out**
Edit your own row. **Expected:** the dialog has no Acceso options, and the row's on/off button is disabled.

**USR-05 · Grant and remove admin**
Edit Carlos Pérez › tick Administrador › Guardar. **Expected:** "Administrador" badge on his row. Edit again ›
untick › Guardar: the badge goes.

**USR-06 · Disable a user**
Carlos Pérez's row › "Desactivar" (the ban icon) › confirm. **Expected:** "La cuenta de Carlos Pérez quedó
desactivada."; he leaves the "Solo activos" list and shows greyed under Estado › Inactivo; signing in as
`lider@demo.test` says "Tu cuenta está desactivada. Contacta al administrador." Turn him back on with the tick
icon.

**USR-07 · Only a super admin edits a super admin**
Make Laura an ordinary admin (USR-05 steps), sign in as her. Usuarios. **Expected:** the edit and on/off buttons on
the super admin's row are disabled. Sign back in as the super admin and remove Laura's admin access.

**USR-08 · Non-admins do not see admin pages**
Sign in as `pm@demo.test`. **Expected:** no Administración section in the sidebar (Tablero appears once she is a
Project Manager somewhere); opening `/users` or `/settings` goes home.

## 3. "Ver como" (super admin)

**IMP-01 · View the app as another user and come back**
Super admin › sidebar › "Ver como". **Expected:** the dropdown lists Laura Gómez and Carlos Pérez with their
roles ("sin proyectos" for now), never admins or inactive users. Pick Laura. **Expected:** the banner "Estás
viendo la aplicación como Laura Gómez…", her menu (no Administración). "Volver a Administrador" returns to the
super admin, without the banner.

**IMP-02 · A new user shows up in "Ver como" right away**
Create a user (USR-01 steps), then open "Ver como". **Expected:** the new user is in the dropdown.

## 4. Settings (Admin)

**SET-01 · Invalid values are refused field by field**
Configuración › Alerta de caja menor baja `0`, Alertas `100, 0` › Guardar. **Expected:** "Revisa los campos
marcados." and a message under both fields; nothing changes. (The límite field only takes amounts: letters and
a minus sign cannot be typed.)

**SET-02 · Save settings**
Límite `750000`, Alertas `100, 75, 75` › Guardar. **Expected:** "Cambios guardados.", the field shows
`75, 100`, and reloading the page keeps the values.

## 5. Projects

**PRJ-01 · An admin with no projects is told to create one**
Super admin › Proyectos. **Expected:** "Aún no hay proyectos. Crea el primero…" with a "Nuevo proyecto" button
under it, and the same button at the end of the filter bar; the legend lists Borrador, Activo, Finalizado,
Archivado.

**PRJ-02 · Create a project**
Nuevo proyecto › `Torre Norte`, description `Edificio de doce pisos`, Inicio `1/10/2026`, Fin `30/06/2026` ›
Crear. **Expected:** "La fecha de fin no puede ser anterior a la de inicio." under Fin planeado. Fin
`30/06/2027` › Crear: the project page opens: Resumen tab, Estado "Borrador", Moneda COP (the Settings
default), the dates written day first, Mi rol "Administrador".

**PRJ-03 · The currency is chosen once**
Editar proyecto. **Expected:** Moneda is disabled; the Estado hint changes with the chosen status. Change the
description › Guardar: the subtitle shows it.

**PRJ-04 · Build the team: one Project Manager**
Equipo del proyecto › Persona `Laura Gómez`, Rol Gerente de proyecto › Agregar. **Expected:** "Laura Gómez ahora
es Gerente de proyecto del proyecto." and a violet row. Carlos Pérez as Gerente › Agregar: "El proyecto ya tiene
un gerente. Cámbiale el rol primero." As Líder de equipo: added, teal row. Admins and disabled users are never in
the Persona list.

**PRJ-05 · Change a role and remove someone**
Carlos's row › Rol › Gerente de proyecto: refused as in PRJ-04. Laura's row › Líder de equipo, then Carlos ›
Gerente: both change. "Quitar" on a row asks "… dejará de ver este proyecto…"; confirming removes the row and
says so. Put the team back as in PRJ-04 (Laura PM, Carlos Team Lead).

**PRJ-06 · Search and filter the list**
Create a second project `Casa 50%`. Proyectos › Buscar `50%`: only "Casa 50%". Estado › Archivado: "Ningún
proyecto coincide con la búsqueda." and "Ver todos", which brings every project back.

**PRJ-07 · Members see only their projects, read-only**
"Ver como" › Carlos Pérez. **Expected:** Proyectos lists only Torre Norte, Mi rol "Líder de equipo", no "Nuevo
proyecto"; the project page has no "Editar proyecto", no Agregar and no Quitar. Opening `/projects/<Casa's id>`
says "Este proyecto no existe o no participas en él."

## 6. Presupuesto y plan

Run as Laura (PM of Torre Norte, from PRJ-04) unless the case says otherwise: sign in as her, or use "Ver como".

**PLN-01 · Categories**
Torre Norte › Presupuesto y plan › Nueva categoría `Materiales` › Agregar, then `Nómina`. **Expected:** both in the
Categorías table with "$ 0". `materiales` again: "Ya existe una categoría con ese nombre." under the field.

**PLN-02 · Build the plan and let the server do the arithmetic**
Agregar etapa `Cimentación` (1/10/2026 – 15/12/2026). Agregar partida: Materiales, `Concreto 3000 PSI`, `m³`,
`12,5`, `35.000,50` (the hint says "Total de la partida: $ 437.506,25") › Agregar partida; Nómina, `Cuadrilla`,
`global`, `1`, `1.000.000`. Agregar hito `Excavación` 40 % planned 20/10/2026, then `Vaciado de zapatas` (the
weight field suggests 60). Etapa `Estructura` (16/12/2026 – 30/04/2027) with Materiales `Acero` `kg` `100`
`30.000` and hito `Columnas y placas` 100. Contingencia (pencil) `500.000`. **Expected:** Total etapas
$ 4.437.506,25, Contingencia $ 500.000, Presupuesto total $ 4.937.506,25; Cimentación 32,4 % del presupuesto,
Estructura 67,6 %; unit price shown as $ 35.000,50 (never rounded).

**PLN-03 · What blocks sending it is listed**
Before the last milestone of PLN-02: "Para enviar el presupuesto falta: … deben sumar 100 %" and Enviar a
aprobación disabled. A milestone that would pass 100 % (e.g. `40,01` when 60 % is used) says "La suma de los
pesos … no puede superar el 100 %." under Peso.

**PLN-04 · Send it: it is locked for everyone**
Enviar a aprobación › confirm. **Expected:** badge "Enviado a aprobación", the note "espera la aprobación…", no
Agregar etapa / partida / hito, no pencil on the contingency; Laura sees no Aprobar or Devolver.

**PLN-05 · The Admin returns it with a comment**
Super admin › Torre Norte › Devolver › `Revisar el precio del acero` › Devolver. **Expected:** "Devuelto con
observaciones" and "Devuelto por Administrador: “Revisar el precio del acero”"; the plan is editable again. Laura
edits the Acero line (pencil) and sends it again.

**PLN-06 · The Admin approves: the baseline is fixed and the project is active**
Aprobar presupuesto › confirm. **Expected:** "Aprobado", the note "Presupuesto aprobado el … Ya no se puede
modificar…", the project's status Activo (Resumen tab and the Proyectos list); Iniciar etapa and Marcar cumplido
appear; stage delete buttons are gone; a stage's name can still be edited but its dates cannot (they are
disabled, with "Las fechas planeadas quedaron fijas…").

**PLN-07 · Progress**
Cimentación › Iniciar etapa (today) › then Excavación › Marcar cumplido with a note. **Expected:** the stage says
"En curso · Iniciada el …", the Excavación row turns green with the date, who and the note; the stage shows 40 %,
and the Avance tile its share of the project (12,4 % with the Acero at $ 32.000 after PLN-05). A milestone whose planned date has gone by and is not met is red ("Atrasado").

**PLN-08 · Only the Admin reopens a milestone; Team Leads see no money**
As Laura: no Reabrir. As the super admin: Reabrir on Excavación puts the stage back to 0 %. Then mark it met again.
"Ver como" Carlos (Team Lead): the plan shows the stages and milestones, with no amounts, no Partidas, no
Categorías amounts and no buttons that change anything.

## 7. Fondos

Run as the super admin unless the case says otherwise. Torre Norte's budget is the one PLN-02 – 06 approved
(Estructura's Acero at $ 32.000 after PLN-05): Cimentación $ 1.437.506,25, Estructura $ 3.200.000, contingency
$ 500.000, total $ 5.137.506,25. Cimentación is in progress with Excavación met (PLN-07).

**FIN-01 · Who sees the money**
As Laura: Torre Norte › Fondos shows the figures and "Aún no hay depósitos…", with no Registrar depósito, no
Usar contingencia and no Finalizar etapa. "Ver como" Carlos (Team Lead): there is no Fondos tab, and opening
`/projects/<id>?tab=finance` shows the plan instead.

**FIN-02 · A deposit split among a stage, the caja menor and the contingency**
Fondos › Registrar depósito: Transferencia, Referencia `TRX-001`; Destino 1 Etapa: Cimentación, Categoría 1
Materiales, Monto 1 `1.500.000`; Agregar destino › Caja menor `300.000`; Agregar destino › Contingencia
`200.000`. The dialog says "Total del depósito: $ 2.000.000" › Registrar. **Expected:** Depositado $ 2.000.000
"de $ 5.137.506,25 presupuestados", Caja menor $ 300.000, Contingencia $ 200.000; Cimentación: Recibido
$ 1.500.000 with "$ 62.493,75 por encima del presupuesto", Financiado 104,4 %, Disponible $ 1.500.000; the
movement "Depósito · Transferencia · TRX-001" lists "Cimentación · Materiales", "Caja menor" and "Contingencia"
with their amounts.

**FIN-03 · Refusals say where**
Registrar depósito with Monto 1 empty › Registrar: the message is under Monto 1 and nothing is saved. Usar
contingencia › Estructura, `250.000`: "El monto supera el saldo disponible de la contingencia." under Monto.

**FIN-04 · Contingency to a stage that ran short**
Usar contingencia › Estructura, `150.000`, Motivo `Sobrecosto de acero` › Pasar a la etapa. **Expected:**
Contingencia $ 50.000 ("usada: $ 150.000"), Estructura Recibido and Disponible $ 150.000, a "Uso de contingencia"
movement with the reason.

**FIN-05 · Proof of deposit**
Adjuntar on the TRX-001 deposit: a text file renamed `.pdf` is refused ("Formato no permitido…" under Archivo);
a real PDF › Adjuntar adds a "Comprobante" link that opens the PDF in a new tab. As Laura the link opens too, and
she has no Adjuntar or Anular.

**FIN-06 · Voiding keeps the record**
Anular the TRX-001 deposit › any motivo: "No se puede anular: parte de ese dinero ya se usó o se trasladó."
(its contingency money was drawn in FIN-04). Registrar depósito › Cimentación `500.000`, Referencia `DUP-1`; then
Anular it with motivo `Duplicado`. **Expected:** the dialog names the deposit (date, amount, destination); the
row turns grey with "Anulado por Administrador el …: “Duplicado”" and loses its buttons; Cimentación's Disponible
is back to $ 1.500.000. Buscar `dup` shows only that movement.

**FIN-07 · Completing a stage carries its money on**
As Laura: Presupuesto y plan › Vaciado de zapatas › Marcar cumplido. Super admin › Fondos › Finalizar etapa on
Cimentación: "Su saldo disponible ($ 1.500.000) pasará a la etapa “Estructura”." › Finalizar etapa.
**Expected:** Cimentación's row turns green (Finalizada) with Disponible $ 0 and "$ 1.500.000 pasaron a la
siguiente"; Estructura Disponible $ 1.650.000; a "Saldo trasladado" movement with no Anular; Presupuesto y plan
shows Cimentación Finalizada; Registrar depósito no longer offers Cimentación.

**FIN-08 · A category holding money stays**
As Laura: Presupuesto y plan › Nueva categoría `Herramienta`. Super admin: Registrar depósito › Estructura,
Categoría Herramienta, `1.000`. As Laura: deleting Herramienta says "La categoría se usa en partidas o gastos y no
se puede eliminar."

## 8. Gastos

After section 7: Estructura holds $ 1.651.000, the caja menor $ 300.000; Cimentación is finalizada.

**EXP-01 · A Team Lead records what they paid**
"Ver como" Carlos › Torre Norte › Gastos › Registrar gasto: `Cemento gris`, Estructura, Materiales, fecha de hoy,
`120.000`, Proveedor `Ferretería El Tornillo` › Registrar. **Expected:** the dialog has no "Se paga desde"; a blue
row "Pendiente" with "Pagado por Carlos Pérez" and "Sin factura o recibo"; Carlos has no Fondos or Caja menor tab.

**EXP-02 · Approval needs a receipt**
Laura › Gastos › Aprobar (check) on Cemento gris › Aprobar: "Adjunta la factura o el recibo antes de aprobar el
gasto." Carlos › Adjuntar recibo › a PDF › Adjuntar: the row gets a "Recibo" link that opens it.

**EXP-03 · Rejected, corrected, sent again**
Laura › Rechazar (ban) › `El valor no coincide con la factura` › Rechazar: a red row "Rechazado: “El valor no
coincide con la factura”". Carlos › Corregir (pencil) › Monto `110.000` › Guardar y enviar de nuevo: blue
"Pendiente" again. Ver gasto (eye): the history lists Registrado, Rechazado with the reason, Corregido with
"Antes: Cemento gris, $ 120.000, …".

**EXP-04 · The PM approves up to the limit**
Laura › Aprobar › Aprobar: green "Aprobado"; the tiles say Por reembolsar 1 ($ 110.000). Fondos: Gastado $ 110.000,
and Estructura's Presupuesto cell says "Gastado: $ 110.000 (3,4%)".

**EXP-05 · Above the limit an Admin approves too**
Carlos records `Alquiler de andamios`, Estructura, `600.000`, with a receipt. Laura › Aprobar: the dialog says
"Supera el límite de $ 500.000…" › Aprobar: the row says "Espera al administrador" and Laura has no Aprobar. The
super admin › Aprobar: "Aprobado".

**EXP-06 · Paying Team Leads back from the caja menor**
Laura › Reembolsar: both expenses listed; tick both › "Reembolsar $ 710.000": "Fondos insuficientes. Disponible:
$ 300.000." under the list. Only Cemento gris › "Reembolsar $ 110.000": its row turns teal "Reembolsado", Ver gasto
shows "Reembolso: <fecha> · Transferencia". Caja menor: saldo $ 190.000.

**EXP-07 · The PM pays from the caja menor or a stage**
Laura › Registrar gasto `Clavos`, `45.000`, Se paga desde Caja menor: green "Aprobado" at once; caja menor
$ 145.000. `Arena`, `100.000`, Fondos de la etapa (Estructura): Aprobado; Fondos: Estructura Disponible
$ 1.551.000. `Mano de obra`, `5.000.000`, Fondos de la etapa: "Fondos insuficientes. Disponible: $ 1.551.000." under
Monto.

**EXP-08 · An Admin voids a mistake; its money goes back**
Super admin › Anular on Arena › motivo `Duplicado` › Anular: grey "Anulado"; Estructura Disponible back to
$ 1.651.000. Reembolsado and Pendiente rows offer no Anular. Carlos's list shows only his two expenses.

## 9. Caja menor

**CAJ-01 · What moved in the cycle**
Laura › Caja menor. **Expected:** Saldo $ 145.000; "Ciclo 1 (actual)" lists the Depósito +$ 300.000 (TRX-001,
with its Comprobante from FIN-05), the Reembolso −$ 110.000 "Reembolso:
Carlos Pérez – Cemento gris" and the Gasto −$ 45.000 "Clavos"; Carlos has no Caja menor tab.

**CAJ-02 · The PM closes the cycle**
Cerrar ciclo: "El ciclo se cierra con un saldo de $ 145.000…" › nota `Fin de mes` › Cerrar ciclo. **Expected:**
Ciclos anteriores has #1, amber ("Cerrado, por firmar"), saldo final $ 145.000; Ciclos por firmar 1; "Ciclo 2
(actual)" has no movements. Laura has no Firmar.

**CAJ-03 · A closed cycle is final**
Super admin › Gastos › Anular on Clavos: "El movimiento pertenece a un ciclo de caja menor cerrado y ya no se
modifica."

**CAJ-04 · The Admin signs it off**
Super admin › Caja menor › Firmar on #1 › Firmar: green "Firmado"; Ver (eye) shows the cycle's movements, "Fin de
mes" and who signed and when.
