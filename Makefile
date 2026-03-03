.PHONY: help up down fresh test cover lint fix horizon logs shell perf load

SAIL = ./vendor/bin/sail

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

up: ## Start all services (app, mysql, redis, horizon)
	$(SAIL) up -d

down: ## Stop all services
	$(SAIL) down

fresh: ## Rebuild containers, migrate, seed
	$(SAIL) up -d --build
	$(SAIL) artisan migrate:fresh

test: ## Run Pest test suite
	$(SAIL) pest

cover: ## Run tests with coverage report
	$(SAIL) pest --coverage --min=90

lint: ## Check code style with Pint (dry-run)
	$(SAIL) pint --test

fix: ## Fix code style with Pint
	$(SAIL) pint

perf: ## Run performance/latency tests only
	$(SAIL) pest --filter=Performance --profile

profile: ## Run all tests with profiling (show slowest)
	$(SAIL) pest --profile

load: ## Run k6 load test (auto-raises rate limit)
	@echo ""
	@echo "\033[33m⚠  Raising API_RATE_LIMIT to 10000 for load testing...\033[0m"
	@sed -i.bak 's/^API_RATE_LIMIT=.*/API_RATE_LIMIT=10000/' .env
	@$(SAIL) artisan config:clear > /dev/null 2>&1
	@echo "\033[32m✓  Rate limit set to 10000 req/min. Running k6...\033[0m"
	@echo ""
	@k6 run tests/Load/k6-flights.js; EXIT_CODE=$$?; \
		echo ""; \
		echo "\033[33m⚠  Restoring API_RATE_LIMIT to 200...\033[0m"; \
		sed -i.bak 's/^API_RATE_LIMIT=.*/API_RATE_LIMIT=200/' .env; \
		rm -f .env.bak; \
		$(SAIL) artisan config:clear > /dev/null 2>&1; \
		echo "\033[32m✓  Rate limit restored to 200 req/min.\033[0m"; \
		exit $$EXIT_CODE

load-smoke: ## Run k6 smoke test (1 VU, 10s)
	k6 run --vus 1 --duration 10s tests/Load/k6-flights.js

horizon: ## Open Horizon dashboard URL
	@echo "http://localhost/horizon"

logs: ## Tail application logs
	$(SAIL) artisan pail

shell: ## Open a shell in the app container
	$(SAIL) shell

migrate: ## Run migrations
	$(SAIL) artisan migrate

test-local: ## Run tests locally (no Sail, uses SQLite)
	./vendor/bin/pest

cover-local: ## Run tests with coverage locally
	./vendor/bin/pest --coverage --min=90

perf-local: ## Run performance tests locally
	./vendor/bin/pest --filter=Performance --profile
