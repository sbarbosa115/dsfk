DC = docker compose
PHP = $(DC) exec -u www-data php
NODE = $(DC) run --rm --no-deps node
# The worker runs as www-data, so the skill scripts (which exec as the default user) never leave root-owned files.
GATE = PHP_SERVICE=worker ~/.claude/skills/symfony-react-app/scripts/gate.sh

.PHONY: up down install migrate admin seed test test-backend test-frontend build api gate fix

up:            ## Start the stack (ports in .env; defaults app :18081, mailpit :18025)
	$(DC) up -d --build

down:
	$(DC) down

install:       ## Install PHP and JS dependencies
	$(PHP) composer install
	$(NODE) npm install

migrate:       ## Run migrations on the dev and test databases
	$(PHP) bin/console doctrine:migrations:migrate -n
	$(PHP) bin/console --env=test doctrine:migrations:migrate -n

admin:         ## Create an admin: make admin EMAIL=you@example.com NAME="Your Name"
	$(DC) exec -it -u www-data php bin/console app:create-admin "$(EMAIL)" "$(NAME)"

seed:          ## Replace the local database with demo data (every password: demo1234). Dev only.
	$(PHP) bin/console doctrine:database:drop --force --if-exists
	$(PHP) bin/console doctrine:database:create
	$(PHP) bin/console doctrine:migrations:migrate -n
	$(PHP) bin/console doctrine:fixtures:load --append -n

test: test-backend test-frontend  ## Full test suite (required before marking work done)

test-backend:
	$(PHP) bin/phpunit

test-frontend:
	$(NODE) sh -c "npx tsc --noEmit && npx vitest run"

api:           ## Regenerate the OpenAPI schema and the UI's TypeScript types from the controllers
	$(DC) exec -T -u www-data php sh -c "bin/console nelmio:apidoc:dump --format=json > assets/react/shared/api/openapi.json"
	$(NODE) npm run -s api:types

gate:          ## Static analysis and code style: every check must pass
	$(GATE)

fix:           ## Apply the formatters, then run the gate
	$(GATE) --fix

build:         ## Build the React app for production into backend/public/build
	$(NODE) npm run build
