# === Client overrides ===
# These targets replace aurora's defaults with client-specific behaviour.

# AURORA_CLIENT_DIR is read by aurora's vite.config.js and app.js to expose
# this client's custom Vue components (and Stimulus controllers, CSS) via the
# @client alias. Place client modules in assets/client/Module/<Name>/...
CLIENT_ASSETS = $(CURDIR)/assets/client
AURORA_ENV    = AURORA_CLIENT_DIR=$(CLIENT_ASSETS)

install: install-dev ## Install the project (alias for install-dev)

stop: ## Stop dev server and kill Vite
	symfony server:stop
	@pkill -f "pnpm.*$(AURORA).*dev" 2>/dev/null || true
	@docker compose stop database 2>/dev/null || true

start: ## Start dev server + Vite dev server
	@docker compose up -d database 2>/dev/null || true
	symfony server:start -d
	@[ -d "$(AURORA)/vendor" ] || $(COMPOSER) install --working-dir=$(AURORA) --no-scripts
	@[ -d "$(AURORA)/node_modules" ] || $(PNPM) --dir=$(AURORA) install
	$(AURORA_ENV) $(PNPM) --dir=$(AURORA) run dev

build: ## Build assets for production
	$(AURORA_ENV) $(PNPM) --dir=$(AURORA) run build

dev: ## Start Vite dev server
	$(AURORA_ENV) $(PNPM) --dir=$(AURORA) run dev

install-dev: ## Install for local development
	$(COMPOSER) install --no-scripts
	$(COMPOSER) install --working-dir=$(AURORA) --no-scripts
	$(COMPOSER) install --working-dir=$(AURORA)/tools/php-cs-fixer
	$(COMPOSER) install --working-dir=$(AURORA)/tools/twig-cs-fixer
	$(COMPOSER) install --working-dir=$(AURORA)/tools/rector
	$(COMPOSER) install --working-dir=$(AURORA)/tools/phpstan
	$(PNPM) --dir=$(AURORA) install
	@ln -sf ../$(AURORA)/public/build public/build
	make setup-dirs
	make db-create
	make migrate
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	$(CONSOLE) aurora:application-parameter
	$(CONSOLE) aurora:menus:sync
	make dev
	@echo "✅ Admin user: admin@aurora.app / password"

install-prod: ## Install for production
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(PNPM) --dir=$(AURORA) install --frozen-lockfile
	make setup-dirs
	make migrate-f
	$(CONSOLE) aurora:application-parameter
	$(CONSOLE) aurora:menus:sync
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
	$(PNPM) --dir=$(AURORA) install --frozen-lockfile; \
	$(CONSOLE) doctrine:migrations:migrate --no-interaction; \
	$(CONSOLE) aurora:application-parameter; \
	$(CONSOLE) aurora:menus:sync; \
	$(AURORA_ENV) $(PNPM) --dir=$(AURORA) run build; \
	APP_ENV=prod APP_DEBUG=0 $(CONSOLE) cache:clear --env=prod; \
	echo "✅ Deployed $$APP_VERSION"

aurora-update: ## Pull latest Aurora changes
	$(COMPOSER) update axelraboit/aurora
	$(COMPOSER) install --working-dir=$(AURORA) --no-scripts
	$(COMPOSER) install --working-dir=$(AURORA)/tools/php-cs-fixer
	$(COMPOSER) install --working-dir=$(AURORA)/tools/twig-cs-fixer
	$(COMPOSER) install --working-dir=$(AURORA)/tools/rector
	$(COMPOSER) install --working-dir=$(AURORA)/tools/phpstan
	$(PNPM) --dir=$(AURORA) install
	make migrate
	@echo "✅ Aurora updated"
