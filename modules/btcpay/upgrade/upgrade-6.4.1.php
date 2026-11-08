<?php

use BTCPay\Constants;
use BTCPay\Installer\OrderStates;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * Italian shops were given the English stock names. Replace those, and leave a name a merchant has edited.
 *
 * @param BTCpay|ModuleInterface|mixed $module
 *
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_6_4_1(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

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

		(new OrderStates($module->name))->localizeExisting($orderState);
		if (!$orderState->save()) {
			return false;
		}
	}

	return true;
}
