<?php

use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay $module
 */
function upgrade_module_3_0_0(ModuleInterface $module): bool
{
	/** @var PDO $connection */
	$connection = Db::getInstance()->connect();

	// Start a transaction so we don't fuck up the database if shit goes wrong
	$connection->beginTransaction();

	$queries = [
		// Rename the table
		'ALTER TABLE `' . _DB_PREFIX_ . 'order_bitcoin` RENAME TO `' . _DB_PREFIX_ . 'bitcoin_payment`',

		// Rename columns
		'ALTER TABLE `' . _DB_PREFIX_ . '_bitcoin_payment`
			CHANGE COLUMN `id_payment` `id` INT(11) NOT NULL AUTO_INCREMENT FIRST,
			CHANGE COLUMN `id_order` `order_id` INT(11) NULL DEFAULT NULL AFTER `cart_id`,
			CHANGE COLUMN `btc_price` `bitcoin_price` VARCHAR(255) NULL DEFAULT NULL AFTER `amount`,
			CHANGE COLUMN `btc_paid` `bitcoin_paid` VARCHAR(255) NULL DEFAULT NULL AFTER `bitcoin_price`,
			CHANGE COLUMN `btc_address` `bitcoin_address` VARCHAR(255) NULL DEFAULT NULL AFTER `bitcoin_paid`,
			CHANGE COLUMN `btc_refundaddress` `bitcoin_refund_address` VARCHAR(255) NULL DEFAULT NULL AFTER `bitcoin_address`;',
	];

	foreach ($queries as $query) {
		// Execute query
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

	return $module->registerHook('displayAdminOrderTop')
		&& $module->registerHook('displayOrderDetail')
		&& $module->registerHook('payment')
		&& $module->registerHook('actionCartSave');
}
