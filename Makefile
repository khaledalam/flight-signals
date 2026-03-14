.PHONY: help up down fresh test cover lint fix horizon logs shell migrate test-local cover-local

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
