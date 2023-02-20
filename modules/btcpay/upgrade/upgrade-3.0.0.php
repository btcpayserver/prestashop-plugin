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
function upgrade_module_3_0_0(mixed $module): bool
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

	return updateHooks($module);
}

function updateDatabase(): bool
{
	/** @var PDO $connection */
	$connection = Db::getInstance()->connect();

	// Start a transaction, so we don't mess up the database if something goes wrong
	$connection->beginTransaction();

	$queries = [
		// Rename the table
		'ALTER TABLE `' . _DB_PREFIX_ . 'order_bitcoin` RENAME TO `' . _DB_PREFIX_ . 'bitcoin_payment`',

		// Rename columns
		'ALTER TABLE `' . _DB_PREFIX_ . 'bitcoin_payment`
			CHANGE COLUMN `id_payment` `id` INT(11) NOT NULL AUTO_INCREMENT FIRST,
			CHANGE COLUMN `id_order` `order_id` INT(11) NULL DEFAULT NULL AFTER `cart_id`,
			CHANGE COLUMN `btc_price` `bitcoin_price` VARCHAR(255) NULL DEFAULT NULL AFTER `amount`,
			CHANGE COLUMN `btc_paid` `bitcoin_paid` VARCHAR(255) NULL DEFAULT NULL AFTER `bitcoin_price`,
			CHANGE COLUMN `btc_address` `bitcoin_address` VARCHAR(255) NULL DEFAULT NULL AFTER `bitcoin_paid`,
			CHANGE COLUMN `btc_refundaddress` `bitcoin_refund_address` VARCHAR(255) NULL DEFAULT NULL AFTER `bitcoin_address`;',
	];

	foreach ($queries as $query) {
		// Execute the query
		if (false === $connection->query($query)) {
			// Cancel the transaction
			if (false === $connection->rollBack()) {
				throw new \RuntimeException('Could not rollback transaction');
			}

			throw new \RuntimeException(json_encode($connection->errorInfo()));
		}
	}

	// Store the made changes in the database
	if (false === $connection->commit()) {
		throw new \RuntimeException(json_encode($connection->errorInfo()));
	}

	return true;
}

function updateConfig(): bool
{
	// Remap the old configuration
	$remapped_config = [
		'btcpay_URL'         => 'BTCPAY_URL',
		'btcpay_LABEL'       => 'BTCPAY_LABEL',
		'btcpay_PAIRINGCODE' => 'BTCPAY_PAIRINGCODE',
		'btcpay_KEY'         => 'BTCPAY_KEY',
		'btcpay_PUB'         => 'BTCPAY_PUB',
		'btcpay_SIN'         => 'BTCPAY_SIN',
		'btcpay_TOKEN'       => 'BTCPAY_TOKEN',
		'btcpay_TXSPEED'     => 'BTCPAY_TXSPEED',
		'btcpay_ORDERMODE'   => 'BTCPAY_ORDERMODE',
	];

	foreach ($remapped_config as $old => $new) {
		if (false === ($previousValue = Configuration::get($old))) {
			throw new \LogicException('Could not get old configuration: ' . $old);
		}

		if (false === Configuration::deleteByName($old)) {
			throw new \LogicException('Could not remove old configuration: ' . $old);
		}

		if (false === Configuration::updateValue($new, $previousValue)) {
			throw new \LogicException('Could not store new configuration: ' . $new);
		}
	}

	// Add old order states to the configuration
	$order_states = [
		Constants::CONFIGURATION_ORDER_STATE_WAITING    => 39,
		Constants::CONFIGURATION_ORDER_STATE_CONFIRMING => 40,
		Constants::CONFIGURATION_ORDER_STATE_FAILED     => 41,
		Constants::CONFIGURATION_ORDER_STATE_PAID       => 42,
	];

	foreach ($order_states as $order_state => $id) {
		if (false === Configuration::updateValue($order_state, $id)) {
			throw new \LogicException('Could not store order state ID for: ' . $order_state);
		}
	}

	return true;
}

function updateHooks(BTCPay $module): bool
{
	return $module->registerHook('displayAdminOrderTop')
		&& $module->registerHook('displayOrderDetail')
		&& $module->registerHook('payment')
		&& $module->registerHook('actionCartSave');
}
