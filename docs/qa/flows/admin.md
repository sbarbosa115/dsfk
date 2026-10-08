# Administration

**Purpose:** Manage the people who sign in (`/users`), the global settings (`/settings`) and review every change
(`/audit`).
**Roles:** Admins only (sidebar section "Administración"). A non-admin opening these routes is redirected to `/`;
the API answers 403 (`ROLE_ADMIN`). Super admins can additionally grant/revoke super admin and edit other super
admins.
**Last updated:** 2026-10-07 (run `issues/runs/2026-10-07-admin-ui.md`)

## Views

### Usuarios · `/users`

Shows: header "Usuarios" / "Las personas que entran a la aplicación. Su rol en cada obra se asigna desde el
proyecto.", filter bar (search "Buscar por nombre o correo…", "Estado": Solo activos (default) / Inactivo / Todos),
"Nuevo usuario", legend Activo/Inactivo, table "Nombre completo" (+ badge "Administrador"/"Super administrador"),
"Correo electrónico", "Proyectos" (project · role, or "Sin proyectos"); row actions pencil "Editar" and the on/off
icon "Desactivar" / "Activar". Inactive rows are greyed. Paged.
States: loading "Cargando…" · empty with filters: "Ningún usuario coincide con la búsqueda." + "Ver todos" · empty:
"Aún no hay usuarios…" + "Nuevo usuario" · error box with "Reintentar".

| Action | Who | Result |
|---|---|---|
| Nuevo usuario | Admin | Modal **Nuevo usuario**: Nombre completo, Correo electrónico, Contraseña (hint "Mínimo 8 caracteres. Compártela con la persona para su primer ingreso."), Acceso: checkbox "Administrador" (hint explains), and for a super admin "Super administrador: Además puede usar "Ver como"…". Submit "Crear" → `POST /api/users`. |
| Editar (pencil) | Admin | Modal **Editar usuario**: same fields, password "Nueva contraseña" (opcional, "Déjala vacía para no cambiarla."); submit "Guardar" → `PATCH /api/users/{id}`. Editing yourself hides "Acceso". Disabled (tooltip "Solo un super administrador puede editar a otro super administrador.") on a super admin's row for a plain admin. |
| Desactivar (ban icon) | Admin | Confirm "Desactivar": "<name> no podrá entrar a la aplicación hasta que vuelvas a activar su cuenta." → `PATCH {active:false}`; green "La cuenta de <name> quedó desactivada." Disabled on your own row and on locked super-admin rows. |
| Activar (check icon) | Admin | No confirm → `PATCH {active:true}`; green "<name> ya puede entrar de nuevo." |

### Configuración · `/settings`

Shows: header "Configuración" / "Valores generales que usan todos los proyectos." and one form: "Moneda por defecto"
(3 letters), "Límite por gasto de líder de equipo" (money in that currency), "Alerta de caja menor baja (%)" (number
1–100), "Alertas de presupuesto (%)" (comma-separated, e.g. "80, 100"), button "Guardar".
States: loading "Cargando…" · error box with "Reintentar" · saved: green "Cambios guardados." and the form shows
what was stored (thresholds sorted, repeats removed) · invalid: red "Revisa los campos marcados." + field errors.

| Action | Who | Result |
|---|---|---|
| Guardar | Admin | `PUT /api/settings`. New default currency applies only to new projects; the limit applies to the next Team Lead approvals. |

### Auditoría · `/audit`

Shows: header "Auditoría" / "Quién cambió qué y cuándo, en todos los proyectos.", filters: search "Nombre de la
persona…", "Proyecto" (Todos + projects), "Registro" (Todos + record types present: Proyecto, Miembro del proyecto,
Etapa, Categoría, Presupuesto, Partida, Hito, Movimiento de dinero, Asiento, Ciclo de caja menor, Gasto, Reembolso,
Configuración, Usuario). Table: Fecha (date-time), Persona (or "Sistema"), Proyecto (or "—"), Registro ("<type>
#id"), Cambio ("Creó"/"Modificó"/"Eliminó" + up to 3 changed field names, "+N"); eye "Ver el cambio" when there are
fields. Paged, newest first.
States: loading · empty "Aún no hay cambios registrados." · filtered empty "Ningún cambio coincide con los filtros."
+ "Ver todos" · error box.

| Action | Who | Result |
|---|---|---|
| Ver el cambio (eye) | Admin | Modal "<type> #id · <action>": who · when, table Campo / Antes / Después ("—" for empty; objects as JSON). Passwords are masked. Read-only. |

## Flows

### Create, use and disable an account
1. `/users` → "Nuevo usuario" → "QA Líder", `qa@demo.test`, password `demo1234`, no access boxes → "Crear" → listed
   active, "Sin proyectos".
2. A project's Resumen → add them as Líder de equipo → `/users` shows "<project> · Líder de equipo".
3. "Desactivar" → confirm → row greyed and hidden under "Solo activos"; their login now fails with "Tu cuenta está
   desactivada…". "Activar" restores it.
4. `/audit`, Registro "Usuario" → the create and update entries, with the password field masked.

### Change a setting
1. `/settings` → Límite 300.000 → "Guardar" → "Cambios guardados.".
2. A project's Gastos tab → "Límite de líder de equipo" shows $ 300.000.

## Rules

- Validation (users): full name required ≤ 150; email valid, ≤ 180, unique ("Ya existe un usuario con ese correo.");
  password 8–4096 (required on create, optional on edit).
- Access rules (users): nobody removes their own admin/super admin or disables themselves
  (`cannot_change_own_access`); only a super admin grants or revokes super admin or edits another super admin
  (`super_admin_required`); super admin implies admin; unchecking admin drops super admin. Admins are global and
  cannot be project members (`admin_is_global`); inactive users cannot be added (`user_inactive`).
- Validation (settings): currency ISO 4217; limit a positive amount; low-balance % 1–100 (integer); 1–5 budget
  thresholds, each 1–200.
- Audit: Doctrine listener records create/update/delete on planning, money, user and settings entities.

## Side effects

- Disabling a user stops their sign-in; their records stay. Settings changes are audited ("Configuración").

## Unclear

- Spec §4 lists project-level overrides for the limit and thresholds; `/settings` is global only.
- The audit "Proyecto" filter loads at most 100 projects (`/projects?perPage=100`). Fine for now?
- Does disabling a signed-in user end their current session at once? (Not checked in code.)

## Sources

- Route: `backend/assets/react/app/router.tsx` (`RequireAdmin`)
- Components: `pages/users/ui/UsersPage.tsx`, `widgets/user-list/ui/UserList.tsx`, `features/user-edit/ui/UserDialog.tsx`,
  `features/user-toggle-active/ui/ToggleActiveButton.tsx`, `entities/user/ui/UserAccess.tsx`,
  `pages/settings/ui/SettingsPage.tsx`, `features/settings-edit/ui/SettingsForm.tsx`, `features/settings-edit/model/percents.ts`,
  `pages/audit/ui/AuditPage.tsx`, `widgets/audit-log/ui/AuditLog.tsx` (all under `backend/assets/react/`).
- API: `GET|POST /api/users`, `PATCH /api/users/{id}` → `backend/src/Identity/UI/Http/Controller/UserController.php`
  (rules `backend/src/Identity/Domain/Model/User.php`); `GET|PUT /api/settings` →
  `backend/src/Settings/UI/Http/Controller/SettingsController.php`; `GET /api/audit` →
  `backend/src/Audit/UI/Http/Controller/AuditController.php`.
