SHELL := /bin/bash
# === Variables ===
PHP_BIN = php
CONSOLE = $(PHP_BIN) bin/console
COMPOSER = composer
PNPM = pnpm
PHP_CS_FIXER = $(PHP_BIN) tools/php-cs-fixer/vendor/bin/php-cs-fixer
TWIG_CS_FIXER = $(PHP_BIN) tools/twig-cs-fixer/vendor/bin/twig-cs-fixer
PHPSTAN = $(PHP_BIN) tools/phpstan/vendor/bin/phpstan
RECTOR = $(PHP_BIN) tools/rector/vendor/bin/rector

# === Build Commands ===
pnpm-setup: ## Setup pnpm via corepack (usage: make pnpm-setup VERSION=10.11.0)
	@if [ -z "$(VERSION)" ]; then \
		echo "Error: Please specify a version. Usage: make pnpm-setup VERSION=x.y.z"; \
		exit 1; \
	fi
	corepack enable
	corepack prepare pnpm@$(VERSION) --activate
	@echo "PNPM $(VERSION) has been activated via corepack"

build:
	$(PNPM) run build

production:
	$(PNPM) install --frozen-lockfile
	$(PNPM) run build


# === Install & Update ===
setup-dirs: ## Create required runtime directories
	@mkdir -p var/cache var/log
	@echo "✅ Runtime directories created"

install-dev:
	$(COMPOSER) install
	$(COMPOSER) install --working-dir=tools/php-cs-fixer
	$(COMPOSER) install --working-dir=tools/twig-cs-fixer
	$(COMPOSER) install --working-dir=tools/rector
	$(COMPOSER) install --working-dir=tools/phpstan
	$(PNPM) install
	make setup-dirs
	make migrate
	make dev

install-prod:
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(PNPM) install --frozen-lockfile
	make setup-dirs
	make migrate-f
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
	$(COMPOSER) install --no-dev --optimize-autoloader; \
	$(PNPM) install --frozen-lockfile; \
	$(CONSOLE) doctrine:migrations:migrate --no-interaction; \
	$(PNPM) run build; \
	APP_ENV=prod APP_DEBUG=0 $(CONSOLE) cache:clear --env=prod; \
	echo "✅ Deployed $$APP_VERSION"

update:
	$(COMPOSER) update
	$(COMPOSER) update --working-dir=tools/php-cs-fixer
	$(COMPOSER) update --working-dir=tools/twig-cs-fixer
	$(COMPOSER) update --working-dir=tools/rector
	$(COMPOSER) update --working-dir=tools/phpstan

autoload: ## Regenerate autoloading according to PSR4
	$(COMPOSER) dump-autoload

autoload-opti: ## Optimize autoloading for caching
	$(COMPOSER) dump-autoload --optimize

outdated: ## Show outdated packages
	$(COMPOSER) outdated

# === Release ===
tag: ## Create and push a new version tag (usage: make tag VERSION=1.2.3)
	@test -n "$(VERSION)" || (echo "❌ Usage: make tag VERSION=1.2.3" && exit 1)
	@git tag -a "$(VERSION)" -m "Release $(VERSION)"
	@git push origin "$(VERSION)"
	@echo "✅ Tag $(VERSION) pushed"

# === Symfony Cache ===
cc-dev:
	$(CONSOLE) cache:clear

cc-prod:
	@echo "Clearing and regenerating production cache..."
	APP_ENV=prod APP_DEBUG=0 $(CONSOLE) cache:clear --env=prod
	@APP_ENV=prod APP_DEBUG=0 $(CONSOLE) about --env=prod >/dev/null 2>&1 || (echo "❌ Cache verification failed: application could not boot" && exit 1)
	@echo "✅ Production cache regenerated successfully"

warmup:
	$(CONSOLE) cache:warmup

purge:
	rm -rf var/cache/* var/logs/*

# === Docker ===
docker-up:
	docker compose up -d database

docker-down:
	docker compose stop database

# === Symfony ===
start:
	@docker compose up -d database 2>/dev/null || true
	symfony server:start -d
	$(PNPM) run dev

start-no-tls:
	symfony server:start --no-tls -d

start-d:
	symfony server:start -d

stop:
	symfony server:stop
	@docker compose stop database 2>/dev/null || true

start-dev-worker: ## Start the messenger worker (async + scheduler)
	@touch var/.messenger-dev-worker-running
	@trap 'rm -f var/.messenger-dev-worker-running; exit' INT TERM EXIT; \
	while true; do $(CONSOLE) messenger:consume async scheduler_main -vv --time-limit=3600 --memory-limit=512M || sleep 1; done

routes:
	$(CONSOLE) debug:router --show-controllers

sf:
	$(CONSOLE)

# === Fixtures & Dev ===
fixtures: ## Drop DB, re-run migrations and load fixtures
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "✅ Fixtures loaded"

fixtures-load:
	$(CONSOLE) doctrine:fixtures:load --no-interaction

fixtures-append:
	$(CONSOLE) doctrine:fixtures:load --append --no-interaction

# === Database ===
migration:
	$(CONSOLE) make:migration

migrate:
	$(CONSOLE) doctrine:migrations:migrate

migrate-f:
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

migrate-prev:
	$(CONSOLE) doctrine:migrations:migrate prev

migration-generate:
	$(CONSOLE) doctrine:migrations:generate

migration-diff:
	$(CONSOLE) doctrine:migrations:diff

schema-validate:
	$(CONSOLE) doctrine:schema:validate -vvv

# === Tests ===
test: test-frontend test-backend ## Run all tests (frontend + backend)

test-backend: db-test ## Run all backend tests (PHPUnit)
	$(PHP_BIN) bin/phpunit --testdox

test-backend-unit: ## Run backend unit tests
	$(PHP_BIN) bin/phpunit --testdox --testsuite=Unit

test-backend-integration: db-test ## Run backend integration tests
	$(PHP_BIN) bin/phpunit --testdox --testsuite=Integration

test-frontend: ## Run frontend unit tests (Vitest)
	$(PNPM) run test

test-e2e: ## Run end-to-end tests (Playwright)
	$(PNPM) run test:e2e

db-test: ## Create and migrate the test database
	$(CONSOLE) doctrine:database:create --env=test --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --env=test --no-interaction

# === Code Quality ===
stan:
	$(PHPSTAN) analyse -c tools/phpstan/phpstan.neon --memory-limit 1G

lint-php:
	$(PHP_CS_FIXER) fix --dry-run --config=.php-cs-fixer.dist.php

lint-js:
	$(PNPM) eslint --config eslint.config.cjs

lint-twig:
	$(TWIG_CS_FIXER)

rector:
	$(RECTOR) process --dry-run -c tools/rector/rector.php

fix-php:
	$(PHP_CS_FIXER) fix --config=.php-cs-fixer.dist.php

fix-js:
	$(PNPM) eslint --config eslint.config.cjs --fix

fix-twig:
	$(TWIG_CS_FIXER) --fix

fix-rector:
	$(RECTOR) process -c tools/rector/rector.php

fix:
	make fix-js
	make fix-twig
	make fix-rector
	make fix-php
	make stan

fd: ## Fix code and build dev assets
	make fix && make dev

ft: ## Fix code and run all tests
	make fix && make test

# === Setup ===
setup-env: ## Create .env.local from .env.local.example template
	@if [ -f .env.local ]; then \
		echo "⚠️  .env.local already exists. Overwrite? (yes/no)"; \
		read -p "" confirm && [ "$$confirm" = "yes" ] || (echo "❌ Cancelled." && exit 1); \
	fi
	cp .env.local.example .env.local
	@echo "✅ .env.local created from .env.local.example — edit it with your local values"

about:
	$(CONSOLE) about

.PHONY: help
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2}'
