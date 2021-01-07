<?php

use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay $module
 */
function upgrade_module_4_0_0(ModuleInterface $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	return updateHooks($module);
}

function updateHooks(BTCPay $module): bool
{
	return $module->unregisterHook('displayAdminOrderTop')
		&& $module->unregisterHook('displayInvoice')
		&& $module->registerHook('displayAdminOrderMainBottom');
}
