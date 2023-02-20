<?php

use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay|ModuleInterface|mixed $module
 */
function upgrade_module_2_0_0(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	// Add invoice reference
	return Db::getInstance()->Execute('ALTER TABLE `' . _DB_PREFIX_ . 'order_bitcoin` ADD invoice_reference varchar(255) AFTER invoice_id');
}
