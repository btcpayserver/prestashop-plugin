MODULE := "btcpay"
MODULE_FOLDER := "./modules"

BUILD_FOLDER := "./build"
ZIP_NAME := "${MODULE}.zip"
ZIP_DEBUG_NAME := "${MODULE}_debug.zip"
MODULE_OUT := "${BUILD_FOLDER}/${ZIP_NAME}"

.PHONY: all build install update upgrade clean lint lint-fix

all: build

build: ## Build the bastard binary file
	# Installing all dependencies
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer install --no-dev

	# Dump autoloader
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer dump-autoload -o --no-dev

	# Removing the old ZIP if present
	@rm -f $(MODULE_OUT)

	# Make the build folder
	@mkdir -p $(BUILD_FOLDER)

	# Copy the license to the module
	@cp ./LICENSE "$(MODULE_FOLDER)/$(MODULE)"

	# Copy the README to the module
	@cp ./README.md "$(MODULE_FOLDER)/$(MODULE)"

	# Zip the module
	@cd $(MODULE_FOLDER) \
		&& zip -r $(ZIP_NAME) $(MODULE) \
		&& mv $(ZIP_NAME) "../$(BUILD_FOLDER)"

debug: ## Build the bastard binary file as debug file
	# Installing all dependencies
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer install

	# Dump autoloader
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer dump-autoload -o

	# Removing the old ZIP if present
	@rm -f $(MODULE_OUT)

	# Make the build folder
	@mkdir -p $(BUILD_FOLDER)

	# Copy the license to the module
	@cp ./LICENSE "$(MODULE_FOLDER)/$(MODULE)"

	# Copy the README to the module
	@cp ./README.md "$(MODULE_FOLDER)/$(MODULE)"

	# Zip the module
	@cd $(MODULE_FOLDER) \
		&& zip -r $(ZIP_DEBUG_NAME) $(MODULE) \
		&& mv $(ZIP_DEBUG_NAME) "../$(BUILD_FOLDER)"

bump: ## Bump all package versions
	# Bump all root dependencies
	@composer install

	# Bump all module dependencies
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer install

install: ## Install everything for development
	# Installing all root dependencies
	@composer install

	# Installing all module dependencies
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer install

update: ## Update all dependencies (including development)
	# Upgrading all root dependencies
	@composer update

	# Upgrading all module dependencies
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer update

upgrade: ## Upgrade all dependencies (including development)
	# Upgrading all root dependencies
	@composer upgrade

	# Upgrading all module dependencies
	@cd "$(MODULE_FOLDER)/$(MODULE)" \
		&& composer upgrade

clean: ## Remove previous builds
	# Removing the ZIP
	@rm -f $(MODULE_OUT)

	# Remove the vendor
	@rm -rf "$(MODULE_FOLDER)/$(MODULE)/vendor"

	# Remove all unnecessary modules
	@ls -d $(MODULE_FOLDER)/* | grep -v $(MODULE) | xargs rm -rf

lint: ## Lints the module
	# Run PHP CS Fixer
	@./vendor/bin/php-cs-fixer fix --diff --dry-run -v

	# Run PHPCS
	@./vendor/bin/phpcs --cache -p

	# Run PHP Parallel Lint
	@./vendor/bin/parallel-lint --exclude ./modules/btcpay/vendor ./modules/btcpay

lint-fix: ## Resolves linter issues
	# Run PHP CS Fixer
	@./vendor/bin/php-cs-fixer fix -v

	# Run PHPCBF
	@./vendor/bin/phpcbf --cache -p

help: ## Display this help screen
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
