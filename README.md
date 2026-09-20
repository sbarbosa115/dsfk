# Control de Proyectos

Construction project tracker: budgets, deposits, expenses, caja menor and milestones.
Functional and technical specification: [docs/SPEC.md](docs/SPEC.md).

- `backend/` — Symfony 8.1 (PHP 8.4+) JSON API, MySQL
- `frontend/` — React + TypeScript + Vite (MUI), UI in Spanish; built into `backend/public/app`

## Local development (Docker)

```bash
make up                 # php/apache :18081, mysql :13306, vite :15173, mailpit :18025
make install
make migrate
make admin EMAIL=admin@example.com NAME="Administrador"
```

Or skip creating users by hand and load the demo data instead:

```bash
make seed               # ⚠ replaces everything in the local database
```

It seeds three projects at different stages — one running with money, expenses and a
closed caja menor cycle, one budget still in draft and one waiting for approval — plus
these accounts, all with the password `demo1234`:

| Correo | Rol |
| --- | --- |
| `admin@demo.test` | Administrador (super administrador: ve "Ver como") |
| `admin2@demo.test` | Administrador corriente (sin "Ver como") |
| `pm@demo.test`, `pm2@demo.test` | Gerente de proyecto |
| `lider@demo.test`, `lider2@demo.test` | Líder de equipo |
| `inactivo@demo.test` | Usuario desactivado |

- Dev UI with hot reload: http://localhost:15173 (proxies `/api` to the PHP container)
- Production-like (built app served by Symfony/Apache): `make build`, then http://localhost:18081
- Captured emails: http://localhost:18025

Ports are overridable so the stack can sit next to other Docker projects — export
`APP_PORT`, `MYSQL_PORT`, `VITE_PORT` or `MAILPIT_PORT`, or set them in a `.env`
file next to `compose.yaml`.

## Deployment

Deployed on cPanel by pulling from GitHub and building on the server:

```bash
~/dsfk-src/deploy/cpanel-update.sh          # latest main (or pass a tag, branch or commit)
```

Setup, cron jobs and backups: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Tests

```bash
make test               # PHPUnit (API, against the MySQL test DB) + tsc + Vitest
```

Every change must pass `make test` in Docker before it is considered done.
