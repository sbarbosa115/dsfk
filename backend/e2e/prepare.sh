#!/usr/bin/env bash
# Prepares this checkout's Docker stack for a smoke run (backend/e2e/smoke.sh calls it): the database reset to the
# demo seed (the same as `make seed`; every password demo1234), no sign-in limit already used, an empty mail
# catcher. Run from anywhere inside the repository.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

console() { docker compose exec -T -u www-data php bin/console "$@"; }

echo "· database: drop, create, migrate, demo seed"
console doctrine:database:drop --force --if-exists -q
console doctrine:database:create -q
console doctrine:migrations:migrate -n -q
console doctrine:fixtures:load --append -n -q

echo "· cache, rate limits, mail catcher, saved sessions"
console cache:clear -q
console cache:pool:clear cache.rate_limiter -q
docker compose exec -T php sh -c 'curl -s -X DELETE http://mailpit:8025/api/v1/messages > /dev/null || true'
rm -rf backend/e2e/.results/auth
echo "· ready"
