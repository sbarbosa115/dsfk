DC = docker compose
PHP = $(DC) exec -u www-data php
NODE = $(DC) run --rm --no-deps node

.PHONY: up down install migrate admin test test-backend test-frontend build

up:            ## Start the stack (app: :8081, vite: :5173, mailpit: :8025)
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

test: test-backend test-frontend  ## Full test suite (required before marking work done)

test-backend:
	$(PHP) bin/phpunit

test-frontend:
	$(NODE) sh -c "npx tsc -b && npx vitest run"

build:         ## Build the React app into backend/public/app
	$(NODE) npx vite build
