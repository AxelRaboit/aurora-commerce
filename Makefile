SHELL := /bin/bash

# === Variables ===
AURORA        = .
PHP_BIN       = php
CONSOLE       = $(PHP_BIN) bin/console
COMPOSER      = composer
PNPM          = pnpm
PHP_CS_FIXER  = $(PHP_BIN) $(AURORA)/tools/php-cs-fixer/vendor/bin/php-cs-fixer
TWIG_CS_FIXER = $(PHP_BIN) $(AURORA)/tools/twig-cs-fixer/vendor/bin/twig-cs-fixer
PHPSTAN       = $(PHP_BIN) $(AURORA)/tools/phpstan/vendor/bin/phpstan
RECTOR        = $(PHP_BIN) $(AURORA)/tools/rector/vendor/bin/rector

# === Build Commands ===
pnpm-setup: ## Setup pnpm via corepack (usage: make pnpm-setup VERSION=10.11.0)
	@if [ -z "$(VERSION)" ]; then \
		echo "Error: Please specify a version. Usage: make pnpm-setup VERSION=x.y.z"; \
		exit 1; \
	fi
	corepack enable
	corepack prepare pnpm@$(VERSION) --activate
	@echo "PNPM $(VERSION) has been activated via corepack"

build: ## Build assets for production
	$(PNPM) --dir=$(AURORA) run build

dev: ## Start Vite dev server
	$(PNPM) --dir=$(AURORA) run dev

production: ## Install + build for production
	$(PNPM) --dir=$(AURORA) install --frozen-lockfile
	$(PNPM) --dir=$(AURORA) run build

# === Install & Update ===
setup-dirs: ## Create required runtime directories
	@mkdir -p var/cache var/log
	@echo "✅ Runtime directories created"

install-dev: ## Install for local development
	$(COMPOSER) install --working-dir=$(AURORA)
	$(COMPOSER) install --working-dir=$(AURORA)/tools/php-cs-fixer
	$(COMPOSER) install --working-dir=$(AURORA)/tools/twig-cs-fixer
	$(COMPOSER) install --working-dir=$(AURORA)/tools/rector
	$(COMPOSER) install --working-dir=$(AURORA)/tools/phpstan
	$(PNPM) --dir=$(AURORA) install
	make setup-dirs
	make migrate
	make sync-params
	make sync-menus
	make i18n
	make dev

install-prod: ## Install for production
	$(COMPOSER) install --no-dev --optimize-autoloader --working-dir=$(AURORA)
	$(PNPM) --dir=$(AURORA) install --frozen-lockfile
	make setup-dirs
	make migrate-f
	make i18n
	make build
	make cc-prod

deploy-prod: ## Deploy to production (requires a git tag on HEAD)
	@APP_VERSION=$$(git describe --exact-match --tags HEAD 2>/dev/null); \
	if [ -z "$$APP_VERSION" ]; then \
		echo "❌ HEAD has no exact git tag. Run: make tag VERSION=x.y.z"; \
		exit 1; \
	fi; \
	echo "🚀 Deploying version $$APP_VERSION..."; \
	echo "$$APP_VERSION" > VERSION; \
	$(COMPOSER) install --no-dev --optimize-autoloader --working-dir=$(AURORA); \
	$(PNPM) --dir=$(AURORA) install --frozen-lockfile; \
	$(CONSOLE) doctrine:migrations:migrate --no-interaction; \
	$(CONSOLE) aurora:application-parameter; \
	$(CONSOLE) aurora:menus:sync; \
	$(CONSOLE) app:translations:dump-js; \
	$(PNPM) --dir=$(AURORA) run build; \
	APP_ENV=prod APP_DEBUG=0 $(CONSOLE) cache:clear --env=prod; \
	echo "✅ Deployed $$APP_VERSION"

update: ## Update all dependencies
	$(COMPOSER) update --working-dir=$(AURORA)
	$(COMPOSER) update --working-dir=$(AURORA)/tools/php-cs-fixer
	$(COMPOSER) update --working-dir=$(AURORA)/tools/twig-cs-fixer
	$(COMPOSER) update --working-dir=$(AURORA)/tools/rector
	$(COMPOSER) update --working-dir=$(AURORA)/tools/phpstan

autoload: ## Regenerate autoloading according to PSR4
	$(COMPOSER) dump-autoload --working-dir=$(AURORA)

autoload-opti: ## Optimize autoloading for caching
	$(COMPOSER) dump-autoload --optimize --working-dir=$(AURORA)

outdated: ## Show outdated packages
	$(COMPOSER) outdated --working-dir=$(AURORA)

# === Release ===
tag: ## Create and push a new version tag (usage: make tag VERSION=1.2.3)
	@test -n "$(VERSION)" || (echo "❌ Usage: make tag VERSION=1.2.3" && exit 1)
	@git tag -a "$(VERSION)" -m "Release $(VERSION)"
	@git push origin "$(VERSION)"
	@echo "✅ Tag $(VERSION) pushed"

# === Symfony Cache ===
cc: ## Clear cache (dev)
	$(CONSOLE) cache:clear

cc-dev: ## Clear cache (dev)
	$(CONSOLE) cache:clear

cc-prod: ## Clear and warm up production cache
	@echo "Clearing and regenerating production cache..."
	APP_ENV=prod APP_DEBUG=0 $(CONSOLE) cache:clear --env=prod
	@APP_ENV=prod APP_DEBUG=0 $(CONSOLE) about --env=prod >/dev/null 2>&1 || (echo "❌ Cache verification failed: application could not boot" && exit 1)
	@echo "✅ Production cache regenerated successfully"

warmup: ## Warm up cache
	$(CONSOLE) cache:warmup

purge: ## Remove all cache and log files
	rm -rf var/cache/* var/logs/*

# === Docker ===
docker-up: ## Start database container
	docker compose up -d database

docker-down: ## Stop database container
	docker compose stop database

# === Symfony ===
start: ## Start dev server + Vite dev server
	@docker compose up -d database 2>/dev/null || true
	symfony server:start -d
	$(PNPM) --dir=$(AURORA) run dev

start-no-tls: ## Start dev server without TLS
	symfony server:start --no-tls -d

start-d: ## Start dev server in background
	symfony server:start -d

stop: ## Stop dev server
	symfony server:stop
	@docker compose stop database 2>/dev/null || true

start-dev-worker: ## Start the messenger worker (async + scheduler)
	@touch var/.messenger-dev-worker-running
	@trap 'rm -f var/.messenger-dev-worker-running; exit' INT TERM EXIT; \
	while true; do $(CONSOLE) messenger:consume async scheduler_main -vv --time-limit=3600 --memory-limit=512M || sleep 1; done

routes: ## List all registered routes
	$(CONSOLE) debug:router --show-controllers

sf: ## Run any Symfony console command (usage: make sf CMD="debug:container")
	$(CONSOLE) $(CMD)

about: ## Show app info
	$(CONSOLE) about

# === Fixtures & Dev ===
fixtures: ## Drop DB, re-run migrations and load fixtures
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "✅ Fixtures loaded"

fixtures-load: ## Load fixtures without dropping DB
	$(CONSOLE) doctrine:fixtures:load --no-interaction

fixtures-append: ## Append fixtures without dropping DB
	$(CONSOLE) doctrine:fixtures:load --append --no-interaction

# === Database ===
db-create: ## Create the database
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop: ## Drop the database
	$(CONSOLE) doctrine:database:drop --force --if-exists

migration: ## Generate a new migration
	$(CONSOLE) make:migration

migrate: ## Run pending migrations
	$(CONSOLE) doctrine:migrations:migrate

migrate-f: ## Run migrations without interaction
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

migrate-prev: ## Rollback last migration
	$(CONSOLE) doctrine:migrations:migrate prev

migration-generate: ## Generate a blank migration
	$(CONSOLE) doctrine:migrations:generate

migration-diff: ## Generate a migration from entity changes
	$(CONSOLE) doctrine:migrations:diff

sync-params: ## Synchronise application parameters (creates missing, deletes obsolete)
	$(CONSOLE) aurora:application-parameter

sync-menus: ## Create missing menus for registered locations (primary, footer, …)
	$(CONSOLE) aurora:menus:sync

i18n: ## Dump Symfony YAML translations to assets/locales/generated/*.json (consumed by vue-i18n)
	$(CONSOLE) app:translations:dump-js

schema-validate: ## Validate the Doctrine schema
	$(CONSOLE) doctrine:schema:validate -vvv

# === Tests ===
test: test-frontend test-backend ## Run all tests (frontend + backend)

test-backend: db-test ## Run all backend tests (PHPUnit)
	$(PHP_BIN) $(AURORA)/bin/phpunit --testdox

test-backend-unit: ## Run backend unit tests
	$(PHP_BIN) $(AURORA)/bin/phpunit --testdox --testsuite=Unit

test-backend-integration: db-test ## Run backend integration tests
	$(PHP_BIN) $(AURORA)/bin/phpunit --testdox --testsuite=Integration

test-frontend: i18n ## Run frontend unit tests (Vitest)
	$(PNPM) --dir=$(AURORA) run test

test-e2e: ## Run end-to-end tests (Playwright)
	$(PNPM) --dir=$(AURORA) run test:e2e

db-test: ## Create and migrate the test database
	$(CONSOLE) doctrine:database:create --env=test --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --env=test --no-interaction

# === Code Quality ===
stan: ## Run PHPStan
	$(PHPSTAN) analyse -c $(AURORA)/tools/phpstan/phpstan.neon --memory-limit 1G

lint-php: ## Check PHP code style (dry-run)
	$(PHP_CS_FIXER) fix --dry-run --config=$(AURORA)/.php-cs-fixer.dist.php

lint-js: ## Check JS code style
	cd $(AURORA) && $(PNPM) eslint --config eslint.config.cjs

lint-twig: ## Check Twig code style
	$(TWIG_CS_FIXER)

rector: ## Run Rector (dry-run)
	$(RECTOR) process --dry-run -c $(AURORA)/tools/rector/rector.php

fix-php: ## Fix PHP code style
	$(PHP_CS_FIXER) fix --config=$(AURORA)/.php-cs-fixer.dist.php

fix-js: ## Fix JS code style
	cd $(AURORA) && $(PNPM) eslint --config eslint.config.cjs --fix

fix-twig: ## Fix Twig code style
	$(TWIG_CS_FIXER) --fix

fix-rector: ## Apply Rector suggestions
	$(RECTOR) process -c $(AURORA)/tools/rector/rector.php

fix: ## Run all fixers + stan
	make fix-js
	make fix-twig
	make fix-rector
	make fix-php
	make stan

fd: ## Fix code and build dev assets
	make fix && make dev

ft: ## Fix code and run all tests
	make fix && make test

# === Client ===
create-client: ## Scaffold a new Aurora client project (prompts for project name)
	@read -p "Project name: " name && \
	test -n "$$name" || (echo "❌ Project name cannot be empty" && exit 1) && \
	bin/create-client "$$name"

# === Setup ===
setup-env: ## Create .env.local from .env.local.example template
	@if [ -f .env.local ]; then \
		echo "⚠️  .env.local already exists. Overwrite? (yes/no)"; \
		read -p "" confirm && [ "$$confirm" = "yes" ] || (echo "❌ Cancelled." && exit 1); \
	fi
	cp .env.local.example .env.local
	@echo "✅ .env.local created from .env.local.example — edit it with your local values"

.PHONY: help
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2}'
