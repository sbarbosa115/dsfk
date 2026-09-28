# UI regression run — 2026-09-28 (rebuild-projects, with the move to Symfony UX and the MDX design)

- **Suite:** [`../ui-regression.md`](../ui-regression.md), at `81b345a` (plus the SET-01 and USR-08 wording fixed
  during this run).
- **Branch:** `feature/rebuild-projects` at `81b345a`, on this checkout's stack: the app served by Symfony on
  http://localhost:18091, built by the `node` service's Encore watch.
- **Data:** reset before the run (drop, create, migrate, `app:create-admin --generate-password`).
- **How:** Chrome through the real UI (values set with native setters and `input`/`change` events, buttons
  clicked); results read from the rendered page; `/api/me` and one curl login where noted.

## Summary

| | Cases |
|---|---|
| Cases in the suite | 24 |
| Pass | 24 |
| Fail | 0 |

## Results

| IDs | Result | Notes |
|---|---|---|
| AUTH-01 – 03 | Pass | Same message for a wrong password and an unknown email; Ingresar disabled until both fields; sidebar sections Obras / Administración / Ayuda and the footer (name, "Super administrador", Tema, Cambiar contraseña, Cerrar sesión) |
| AUTH-04 | Pass | As Laura: "La contraseña actual no es correcta." then "Contraseña actualizada."; still signed in (`/api/me`), and only the new password signs in (checked with curl) |
| AUTH-05 | Pass | `ana@demo.test`: "Demasiados intentos…" |
| USR-01 – 06 | Pass | Rows green (Activo), no badge, "Sin proyectos"; `email_taken`; three field messages; own row with no Acceso options and the on/off disabled; Administrador badge added then removed; Desactivar asks, says "La cuenta de Carlos Pérez quedó desactivada.", the row moves to Inactivo greyed, and signing in as him says "Tu cuenta está desactivada…" |
| USR-07 | Pass | Laura as ordinary admin: edit and on/off disabled on the super admin's row (tooltip explains), PATCH refused 403 |
| USR-08 | Pass | Laura (PM of Torre Norte): no Administración; `/users` sends her to /dashboard |
| IMP-01 – 02 | Pass | Dropdown lists only active non-admins with their roles; banner and reduced menu; way back; a user created meanwhile appears at once |
| SET-01 – 02 | Pass | Two fields marked; saved as `750.000` and `75, 100` |
| PRJ-01 – 07 | Pass | Empty state with the action; end-before-start refused, then created (Borrador, COP, dates day first); currency disabled, status hint follows the choice; one PM (refusal message), roles by colour, role change in the row, Quitar confirmation "… Sus gastos y registros se conservan."; `50%` search, Archivado filtered to nothing, "Ver todos" clears both filters; as Carlos (Team Lead) only his project, read-only, and the other project "no existe o no participas en él" |

## Findings

None in the app. Two suite texts were out of date: SET-01 asked to type `-5` in an amount field that does not
accept a minus sign (now uses the caja menor %), and USR-08 did not expect Tablero for a PM.

## Conditions

- The profile's password manager extension injects a frame when a password field shows and then blocks the
  automation's scripts on that tab. Steps with password fields were started in one call and read back with the
  page text; switching accounts outside the cases under test used `/api/login`.
- The console shows `TypeError: Cannot read properties of undefined (reading 'global')` at `<page>:0:0` on every
  page, including the raw JSON of `/api/me` where no app script runs: it comes from that extension, not the app.
- A script was cut by an extension disconnect during PRJ-04/05; the state it left was checked and the rest of the
  steps were run again one by one.
