# Shell and authentication

**Purpose:** Signing in and out, the signed-in layout (sidebar, theme, password), "Ver como" (view the app as another
user, for testing), the in-app documentation and the not-found page.
**Roles:** Everyone signs in, changes their own password, picks a theme and reads the help. Only a **super admin**
sees "Ver como". Only admins see the Administración section of the sidebar. PMs and Team Leads differ only in what
the sidebar lists (below).
**Last updated:** 2026-10-07 (run `issues/runs/2026-10-07-admin-ui.md`)

Demo data (`make seed`): every account's password is `demo1234`. `admin@demo.test` (Sofía Restrepo, super admin),
`admin2@demo.test` (Julián Mesa, admin), `pm@demo.test`, `pm2@demo.test`, `lider@demo.test`, `lider2@demo.test`.

## Views

### Login · `/login`

Shows: card with "Control de Proyectos" / "Obras, dinero y avance", heading "Iniciar sesión", fields "Correo
electrónico" and "Contraseña", button "Ingresar", note "¿No tienes cuenta? Pídesela al administrador de la obra."
States: busy: button shows a spinner · error: red alert above the fields · already signed in: redirects to `/`.

| Action | Who | Result |
|---|---|---|
| Ingresar | anyone | `POST /api/login`. OK → goes back to the page the user was sent from (only same-origin paths), else `/`. |
| Wrong email/password | anyone | Alert "Correo o contraseña incorrectos." |
| Disabled account | anyone | Alert "Tu cuenta está desactivada. Contacta al administrador." |
| 5 failed attempts | anyone | Alert "Demasiados intentos. Intenta de nuevo en unos minutos." (Symfony login throttling, max 5). |

The button is disabled until both fields have text. The form has `noValidate`, so the browser does not check the
email format.

### Home redirect · `/`

Admins and PMs go to `/dashboard`; Team Leads (and users with no project) go to `/projects`. Any route behind the
login without a session goes to `/login` and comes back after signing in. While the session loads: full-page
spinner.

### App shell (every signed-in page)

Sidebar (on narrow screens a drawer opened by the "Menú" button in the top bar; it closes on navigation, Escape,
the backdrop or "Cerrar menú"):

- Brand: "Control de Proyectos" · "Obras, dinero y avance".
- **Ver como** select (super admin only, see below).
- **Obras**: "Tablero" (`/dashboard`, Admins and PMs only), "Proyectos" (`/projects`).
- **Administración** (admins only): "Usuarios", "Configuración", "Auditoría".
- **Ayuda**: "Documentación" (`/help`).
- Footer: the signed-in person's name and role ("Super administrador", "Administrador", or their project roles
  joined by " · ", or "Sin rol global"); **Tema** select; "Cambiar contraseña"; "Cerrar sesión".

The active link is highlighted. Pages load lazily: the sidebar stays and the page area shows "Cargando…".

| Action | Who | Result |
|---|---|---|
| Tema → Claro / Oscuro / Según el dispositivo | all | Applied at once; stored in this browser only. |
| Cambiar contraseña | all | Opens the **Cambiar contraseña** modal (below). |
| Cerrar sesión | all | `POST /api/logout`, then `/login`. |

### Cambiar contraseña (modal, from the sidebar footer)

Fields: "Contraseña actual", "Nueva contraseña" (hint "Mínimo 8 caracteres."). Buttons "Cancelar", "Guardar".
OK → green "Contraseña actualizada.", the fields are cleared and the modal **stays open**.
Errors under the field: wrong current password; new password shorter than 8; new equal to current.

### Ver como (super admin only)

A select in the sidebar listing "Nadie (mi cuenta)" plus every **active, non-admin** user as
"<name> · <role> <project>, …" (or "· sin proyectos"). Tooltip: "Para pruebas: navegas con los permisos de esa
persona y lo que hagas queda a su nombre, indicando que fuiste tú."

| Action | Who | Result |
|---|---|---|
| Pick a user | super admin | `POST /api/impersonate?_switch_user=<email>`; the app reloads as that user and goes to `/`. A banner on every page: "Estás viendo la aplicación como <name>. Lo que hagas quedará registrado a su nombre (vía <admin>)." The sidebar footer still shows the super admin's name, with role "Super administrador". |
| "Volver a <admin>" (banner) or pick "Nadie (mi cuenta)" | while impersonating | `POST …?_switch_user=_exit`; back to the super admin, at `/`. |
| Switch fails | | "No se pudo cambiar de usuario." under the select. |

A GET with `_switch_user` is refused (403 `switch_user_not_allowed`): switching only by POST.
Plain admins (`admin2@demo.test`) do not see the select.

### Documentación · `/help` and `/help/:id`

`/help` shows "Documentación", "Guías paso a paso de lo que puedes hacer en la aplicación.", a search box ("Buscar
en la ayuda", placeholder "Ej.: registrar gasto, caja menor, contraseña…", waits 300 ms after typing, ignores
accents), and one card per guide for the user's roles. Admins also get the checkbox "Ver todas las guías (también
las de otros roles)". No match: "Ninguna guía coincide con la búsqueda. Prueba con otras palabras."
An admin sees by default: primeros-pasos, roles-y-permisos, tablero, proyectos, categorias-y-etapas, partidas,
hitos, aprobar-presupuesto, depositos, contingencia, avance-etapas, registrar-gasto, aprobar-gastos, reembolsos,
caja-menor, firmar-ciclo, usuarios, configuracion, auditoria (+ ver-como for a super admin).

`/help/:id` shows "← Volver a la documentación", title and summary, sections with numbered steps, info notes and
screenshots, and "Guías relacionadas". Unknown id: "Esta guía no existe." Content is static (no API call).

### Not found · any unknown route behind the login

Heading "Esta página no existe." and a button "Ir al inicio" (`/`). Shown inside the shell.
`/users`, `/settings`, `/audit` for a non-admin redirect to `/` (not to this page).

## Flows

### Sign in and land

1. Open `/projects/1` signed out → redirected to `/login`.
2. Sign in as `admin@demo.test` → back on `/projects/1`.
3. Sign out → `/login`. Opening any app route again redirects to `/login`.

### View as a Team Lead and come back

1. As super admin, Ver como → "Carlos Ruiz · Líder de equipo Torre Norte" → lands on `/projects`, banner shown,
   no "Tablero" nor Administración in the sidebar.
2. Click "Volver a Sofía Restrepo" → back to `/dashboard` as the super admin.

## Rules

- Validation: login needs both fields; new password 8–4096 characters and different from the current one.
- Access: guards are client-side (`RequireSession`, `RequireAdmin`); the API enforces the same (`ROLE_ADMIN` on
  `/api/users`, `PUT /api/settings`, `/api/audit`). Impersonation needs `CAN_SWITCH_USER` (super admin).
- Theme lives in this browser only.

## Side effects

- Actions taken while impersonating are recorded under the impersonated user, with the super admin noted.

## Unclear

- Should the "Cambiar contraseña" modal close itself after success (today it stays open with a success alert)?
- Spec §2 has no "super admin" role; "Ver como" is kept on purpose (owner decision), but the spec does not describe it.

## Sources

- Route: `backend/assets/react/app/router.tsx`, guards `backend/assets/react/app/guards.tsx`
- Components: `pages/login/ui/LoginPage.tsx`, `features/auth-login/ui/LoginForm.tsx`, `widgets/app-shell/ui/AppShell.tsx`,
  `features/change-password/ui/ChangePasswordButton.tsx`, `features/impersonate/ui/ImpersonationPicker.tsx`,
  `features/impersonate/ui/ImpersonationBanner.tsx`, `shared/ui/ThemePicker.tsx`, `shared/lib/theme.tsx`,
  `pages/help/ui/HelpPage.tsx`, `pages/help/ui/HelpTopicPage.tsx`, `pages/help/content/topics.es.json`,
  `pages/not-found/ui/NotFoundPage.tsx` (all under `backend/assets/react/`); strings in `shared/i18n/es.ts`.
- API: `POST /api/login`, `POST /api/logout`, `GET /api/me`, `POST /api/me/password`, `GET|POST /api/impersonate`
  → `backend/src/Identity/UI/Http/Controller/AuthController.php`; switch guard
  `backend/src/Identity/UI/Http/Security/SwitchUserGuardListener.php`, voter
  `backend/src/Identity/Infrastructure/Security/ImpersonationVoter.php`; firewall `backend/config/packages/security.yaml`.
