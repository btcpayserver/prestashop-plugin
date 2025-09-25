# --- Variables ---
MODULE          := btcpay
MODULE_FOLDER   := ./modules
MODULE_PATH     := $(MODULE_FOLDER)/$(MODULE)

BUILD_FOLDER    := ./build
ZIP_NAME        := $(MODULE).zip
MODULE_OUT      := $(BUILD_FOLDER)/$(ZIP_NAME)

# --- Colors ---
BLUE := \033[36m
RESET := \033[0m

# --- Targets ---
.PHONY: all deps build bump update upgrade clean lint lint-fix help

all: build

deps: ## Install and prepare all dependencies
	@echo "$(BLUE)==> Installing dependencies...$(RESET)"
	@cd "$(MODULE_PATH)" && symfony composer install
	@echo "$(BLUE)==> Dumping autoloader...$(RESET)"
	@cd "$(MODULE_PATH)" && symfony composer dump-autoload -o --no-dev

build: deps ## Build the module and create ZIP
	@echo "$(BLUE)==> Building module...$(RESET)"
	@rm -f "$(MODULE_OUT)"
	@mkdir -p "$(BUILD_FOLDER)"
	@cp ./LICENSE "$(MODULE_PATH)"
	@cp ./README.md "$(MODULE_PATH)"
	@cd "$(MODULE_FOLDER)" && zip -r "$(ZIP_NAME)" "$(MODULE)" && mv "$(ZIP_NAME)" "../$(BUILD_FOLDER)"
	@echo "$(BLUE)==> Build complete: $(MODULE_OUT)$(RESET)"

bump: ## Bump all PHP dependencies
	@echo "$(BLUE)==> Bumping root dependencies...$(RESET)"
	@symfony composer bump
	@echo "$(BLUE)==> Bumping module dependencies...$(RESET)"
	@cd "$(MODULE_PATH)" && symfony composer bump

update: ## Update all dependencies (including dev)
	@echo "$(BLUE)==> Updating root dependencies...$(RESET)"
	@symfony composer update
	@echo "$(BLUE)==> Updating module dependencies...$(RESET)"
	@cd "$(MODULE_PATH)" && symfony composer update

upgrade: ## Upgrade all dependencies (including dev, with constraints)
	@echo "$(BLUE)==> Upgrading root dependencies...$(RESET)"
	@symfony composer upgrade -W
	@echo "$(BLUE)==> Upgrading module dependencies...$(RESET)"
	@cd "$(MODULE_PATH)" && symfony composer upgrade -W

clean: ## Remove previous builds and vendor files
	@echo "$(BLUE)==> Cleaning build artifacts...$(RESET)"
	@rm -f "$(MODULE_OUT)"
	@rm -rf "$(MODULE_PATH)/vendor"
	@find "$(MODULE_FOLDER)" -mindepth 1 -maxdepth 1 -type d ! -name "$(MODULE)" -exec rm -rf {} +

lint: ## Run linter (dry-run mode)
	@echo "$(BLUE)==> Running PHP CS Fixer (dry-run)...$(RESET)"
	@symfony php ./vendor/bin/php-cs-fixer fix --diff --dry-run -v
	@echo "$(BLUE)==> Running PHPCS...$(RESET)"
	@symfony php ./vendor/bin/phpcs -p

lint-fix: ## Auto-fix linter issues
	@echo "$(BLUE)==> Fixing code style with PHP CS Fixer...$(RESET)"
	@symfony php ./vendor/bin/php-cs-fixer fix -v
	@echo "$(BLUE)==> Running PHPCBF...$(RESET)"
	@symfony php ./vendor/bin/phpcbf -p

help: ## Show this help message
	@echo "$(BLUE)Available targets:$(RESET)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(BLUE)%-15s$(RESET) %s\n", $$1, $$2}'
