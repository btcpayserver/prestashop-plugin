<?php

use BTCPay\Constants;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay|ModuleInterface|mixed $module
 */
function upgrade_module_6_1_0(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	// Set a sane default for the new protection mode (which is true).
	Configuration::set(Constants::CONFIGURATION_PROTECT_ORDERS, true);

	// And we are done
	return true;
}
