<?php

use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay $module
 */
function upgrade_module_5_1_0(ModuleInterface $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	return updateConfig();
}

function updateConfig(): bool
{
	// Remove old configuration
	$removedConfig = ['BTCPAY_ORDERMODE'];
	foreach ($removedConfig as $name) {
		if (false === Configuration::deleteByName($name)) {
			throw new \LogicException('Could not remove old configuration: ' . $name);
		}
	}

	return true;
}
