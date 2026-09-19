# Control de Proyectos

Construction project tracker: budgets, deposits, expenses, caja menor and milestones.
Functional and technical specification: [docs/SPEC.md](docs/SPEC.md).

- `backend/` — Symfony 7.4 (PHP 8.2+) JSON API, MySQL
- `frontend/` — React + TypeScript + Vite (MUI), UI in Spanish; built into `backend/public/app`

## Local development (Docker)

```bash
make up                 # php/apache :8080, mysql :3307, vite :5173, mailpit :8025
make install
make migrate
make admin EMAIL=admin@example.com NAME="Administrador"
```

- Dev UI with hot reload: http://localhost:5173 (proxies `/api` to the PHP container)
- Production-like (built app served by Symfony/Apache): `make build`, then http://localhost:8080
- Captured emails: http://localhost:8025

## Release and deployment

```bash
deploy/build-release.sh 1.0.0   # → build/control-proyectos-1.0.0.zip (verified on PHP 8.2)
```

Installation, updates, cron jobs and backups on cPanel: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Tests

```bash
make test               # PHPUnit (API, against the MySQL test DB) + tsc + Vitest
```

Every change must pass `make test` in Docker before it is considered done.
