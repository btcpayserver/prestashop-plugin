<?php

use BTCPay\Constants;
use BTCPay\Installer\OrderStates;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * 6.4.3 ran this before the module-file fallback existed, so shops on 6.4.3 still have English names.
 *
 * @param BTCpay|ModuleInterface|mixed $module
 *
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_6_4_4(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	$orderStates = new OrderStates($module->name);
	foreach ([
		Constants::CONFIGURATION_ORDER_STATE_WAITING,
		Constants::CONFIGURATION_ORDER_STATE_CONFIRMING,
		Constants::CONFIGURATION_ORDER_STATE_FAILED,
		Constants::CONFIGURATION_ORDER_STATE_PAID,
	] as $configurationKey) {
		$orderState = new OrderState((int) Configuration::get($configurationKey));
		if (!\Validate::isLoadedObject($orderState)) {
			continue;
		}

		$orderStates->localizeExisting($orderState);
		if (!$orderState->save()) {
			return false;
		}
	}

	return true;
}
