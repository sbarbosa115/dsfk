# Control de Proyectos

Construction project tracker: budgets, deposits, expenses, caja menor and milestones.
Functional and technical specification: [docs/SPEC.md](docs/SPEC.md).

- `backend/` — Symfony 8.1 (PHP 8.4+) JSON API, MySQL
- `frontend/` — React + TypeScript + Vite (MUI), UI in Spanish; built into `backend/public/app`

## Local development (Docker)

```bash
make up                 # php/apache :8081, mysql :3307, vite :5173, mailpit :8025
make install
make migrate
make admin EMAIL=admin@example.com NAME="Administrador"
```

- Dev UI with hot reload: http://localhost:5173 (proxies `/api` to the PHP container)
- Production-like (built app served by Symfony/Apache): `make build`, then http://localhost:8081
- Captured emails: http://localhost:8025

## Deployment

Deployed on cPanel by pulling from GitHub and building on the server:

```bash
~/dsfk-src/deploy/deploy.sh          # latest main (or pass a tag, branch or commit)
```

Setup, cron jobs and backups: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Tests

```bash
make test               # PHPUnit (API, against the MySQL test DB) + tsc + Vitest
```

Every change must pass `make test` in Docker before it is considered done.
