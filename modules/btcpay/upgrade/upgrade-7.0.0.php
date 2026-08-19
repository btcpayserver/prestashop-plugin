<?php

use BTCPay\Constants;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay|ModuleInterface|mixed $module
 *
 * @throws JsonException
 */
function upgrade_module_7_0_0(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	if (false === unregisterStaleHooks($module)) {
		return false;
	}

	if (false === dropObsoleteColumns()) {
		return false;
	}

	if (false === addCurrencyIsoColumn()) {
		return false;
	}

	if (false === removeLegacyConfiguration()) {
		return false;
	}

	return ensureConfigurationDefaults();
}

function unregisterStaleHooks(BTCPay $module): bool
{
	foreach (['payment', 'displayPaymentEU', 'displayAdminOrderTop', 'displayInvoice'] as $hook) {
		$module->unregisterHook($hook);
	}

	return true;
}

/**
 * @throws JsonException
 */
function dropObsoleteColumns(): bool
{
	/** @var PDO $connection */
	$connection = Db::getInstance()->connect();

	$connection->beginTransaction();

	$queries = [
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_price;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_paid;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_address;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS bitcoin_refund_address;',
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment` DROP IF EXISTS rate;',
	];

	foreach ($queries as $query) {
		if (false === $connection->query($query)) {
			if (false === $connection->rollBack()) {
				throw new RuntimeException('Could not rollback transaction');
			}

			throw new RuntimeException(json_encode($connection->errorInfo(), JSON_THROW_ON_ERROR));
		}
	}

	if (false === $connection->commit()) {
		throw new RuntimeException(json_encode($connection->errorInfo(), JSON_THROW_ON_ERROR));
	}

	return true;
}

/**
 * @throws JsonException
 */
function addCurrencyIsoColumn(): bool
{
	/** @var PDO $connection */
	$connection = Db::getInstance()->connect();
	$table      = '`' . _DB_PREFIX_ . 'bitcoin_payment`';
	$existing   = $connection->query('SHOW COLUMNS FROM ' . $table . " LIKE 'currency_iso'");

	if (false === $existing || false === $existing->fetch()) {
		if (false === $connection->query('ALTER TABLE ' . $table . ' ADD `currency_iso` CHAR(3) NULL')) {
			throw new RuntimeException(json_encode($connection->errorInfo(), JSON_THROW_ON_ERROR));
		}
	}

	return true;
}

function removeLegacyConfiguration(): bool
{
	$removedConfig = [
		'BTCPAY_LABEL',
		'BTCPAY_PAIRINGCODE',
		'BTCPAY_KEY',
		'BTCPAY_PUB',
		'BTCPAY_SIN',
		'BTCPAY_TOKEN',
		'btcpay_URL',
		'btcpay_LABEL',
		'btcpay_PAIRINGCODE',
		'btcpay_KEY',
		'btcpay_PUB',
		'btcpay_SIN',
		'btcpay_TOKEN',
		'btcpay_TXSPEED',
		'btcpay_ORDERMODE',
	];

	foreach ($removedConfig as $name) {
		if (Configuration::hasKey($name)) {
			Configuration::deleteByName($name);
		}
	}

	return true;
}

function ensureConfigurationDefaults(): bool
{
	if (!Configuration::hasKey(Constants::CONFIGURATION_PROTECT_ORDERS)) {
		Configuration::updateValue(Constants::CONFIGURATION_PROTECT_ORDERS, true);
	}

	if (!Configuration::hasKey(Constants::CONFIGURATION_ORDER_MODE)) {
		Configuration::updateValue(Constants::CONFIGURATION_ORDER_MODE, Constants::ORDER_MODE_BEFORE);
	}

	return true;
}
