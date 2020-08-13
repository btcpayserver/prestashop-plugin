MODULE := "btcpay"
MODULE_FOLDER := "./modules"

BUILD_FOLDER := "./build"
ZIP_NAME := "${MODULE}.zip"
MODULE_OUT := "${BUILD_FOLDER}/${ZIP_NAME}"

.PHONY: all deps build clean lint lint-fix

all: build

deps: ## Download and make all dependencies
	# Installing all dependencies
	@composer install

	# Copy over the Bitpay lib
	@mkdir -p "$(MODULE_FOLDER)/$(MODULE)/lib/Bitpay"
	@cp -r ./vendor/bitpay/php-client/src/Bitpay "$(MODULE_FOLDER)/$(MODULE)/lib/Bitpay"

build: deps ## Build the bastard binary file
	# Make the build folder
	@mkdir -p $(BUILD_FOLDER)

	# Zip the module
	@cd $(MODULE_FOLDER) \
		&& zip -r $(ZIP_NAME) $(MODULE) \
		&& mv $(ZIP_NAME) "../$(BUILD_FOLDER)"

clean: ## Remove previous builds
	# Removing the ZIP
	@rm -f $(MODULE_OUT)

	# Remove the Bitpay lib
	@rm -rf "$(MODULE_FOLDER)/$(MODULE)/lib"

	# Remove all unnecessary modules
	@ls -d $(MODULE_FOLDER)/* | grep -v $(MODULE) | xargs rm -rf

lint: ## Lints the module
	@./bin/php-cs-fixer fix --diff --dry-run -v

lint-fix: ## Resolves linter issues
	@./bin/php-cs-fixer fix -v

help: ## Display this help screen
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
