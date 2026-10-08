# Smoke suite

The simple cases of [`docs/tests/ui-regression.md`](../../docs/tests/ui-regression.md) as Playwright tests, run
against this checkout's Docker stack before anything is checked by hand (`symfony-react-app` skill, step 8).

```bash
backend/e2e/smoke.sh                          # reset the stack to the demo seed, then run everything
backend/e2e/smoke.sh ui                       # one spec
SMOKE_KEEP_DATA=1 backend/e2e/smoke.sh -g "UI-03"    # one case, without resetting first
```

| File | What it holds |
|---|---|
| `prepare.sh` | the stack reset: database to the demo seed (`make seed`), sign-in limits cleared, mail catcher emptied |
| `smoke.sh` | `prepare.sh`, then Playwright in the `e2e` container (`docker compose --profile e2e`) |
| `support/test.ts` | the demo accounts and projects, and `signedInAs(email)`, which signs in through the login form once per run |
| `ui.spec.ts` | § 13 Interfaz: UI-01 – UI-08, UI-10 |

Each test is named by its case ID, finds things as a person does (role, label, text) and never waits a fixed time.
A failing test leaves its trace and a screenshot in `e2e/.results/artifacts`; the report is
`e2e/.results/report.json` (and `html/`).
