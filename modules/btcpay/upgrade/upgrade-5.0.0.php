<?php

use BTCPay\Constants;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay|ModuleInterface|mixed $module
 *
 * @throws JsonException
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_5_0_0(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	if (false === updateDatabase()) {
		return false;
	}

	if (false === updateConfig()) {
		return false;
	}

	return updateOrderStates();
}

/**
 * @throws JsonException
 */
function updateDatabase(): bool
{
	/** @var PDO $connection */
	$connection = Db::getInstance()->connect();

	// Start a transaction, so we don't mess up the database if stuff goes wrong
	$connection->beginTransaction();

	$queries = [
		// Drop columns
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_price;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_paid;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_address;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_refund_address;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS rate;',
	];

	foreach ($queries as $query) {
		// Execute query
		if (false === $connection->query($query)) {
			// Cancel the transaction
			if (false === $connection->rollBack()) {
				throw new \RuntimeException('Could not rollback transaction');
			}

			throw new \RuntimeException(\json_encode($connection->errorInfo(), \JSON_THROW_ON_ERROR));
		}
	}

	// Store the made changes in the database
	if (false === $connection->commit()) {
		throw new \RuntimeException(\json_encode($connection->errorInfo(), \JSON_THROW_ON_ERROR));
	}

	return true;
}

function updateConfig(): bool
{
	// Remove old configuration
	$removedConfig = ['BTCPAY_LABEL', 'BTCPAY_PAIRINGCODE', 'BTCPAY_KEY', 'BTCPAY_PUB', 'BTCPAY_SIN', 'BTCPAY_TOKEN'];
	foreach ($removedConfig as $name) {
		if (false === Configuration::deleteByName($name)) {
			throw new \LogicException('Could not remove old configuration: ' . $name);
		}
	}

	// Remap the old speed configuration
	$speedMode = Constants::CONFIGURATION_SPEED_MODE;
	switch ($current = Configuration::get($speedMode)) {
		case 'low':
			if (false === Configuration::updateValue($speedMode, InvoiceCheckoutOptions::SPEED_LOW)) {
				throw new \LogicException(sprintf('Could not change %s to %s', $speedMode, InvoiceCheckoutOptions::SPEED_LOW));
			}

			break;
		case 'medium':
			if (false === Configuration::updateValue($speedMode, InvoiceCheckoutOptions::SPEED_MEDIUM)) {
				throw new \LogicException(sprintf('Could not change %s to %s', $speedMode, InvoiceCheckoutOptions::SPEED_MEDIUM));
			}

			break;
		case 'high':
			if (false === Configuration::updateValue($speedMode, InvoiceCheckoutOptions::SPEED_HIGH)) {
				throw new \LogicException(sprintf('Could not change %s to %s', $speedMode, InvoiceCheckoutOptions::SPEED_HIGH));
			}

			break;
		default:
			throw new \LogicException(sprintf('Could not find proper value for config %s, current value is %s', $speedMode, $current));
	}

	return true;
}

/**
 * @throws PrestaShopException
 * @throws PrestaShopDatabaseException
 */
function updateOrderStates(): bool
{
	$waiting      = new OrderState(Configuration::get(Constants::CONFIGURATION_ORDER_STATE_WAITING));
	$confirmation = new OrderState(Configuration::get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING));
	$failed       = new OrderState(Configuration::get(Constants::CONFIGURATION_ORDER_STATE_FAILED));
	$paid         = new OrderState(Configuration::get(Constants::CONFIGURATION_ORDER_STATE_PAID));

	foreach (Language::getLanguages(true, false, true) as $languageId) {
		$waiting->name[$languageId]      = 'Awaiting crypto payment';
		$confirmation->name[$languageId] = 'Waiting for confirmations';
		$failed->name[$languageId]       = 'Crypto transaction failed';
		$paid->name[$languageId]         = 'Paid with crypto';
	}

	return $waiting->save() && $confirmation->save() && $failed->save() && $paid->save();
}
