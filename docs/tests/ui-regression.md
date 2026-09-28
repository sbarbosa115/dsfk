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
