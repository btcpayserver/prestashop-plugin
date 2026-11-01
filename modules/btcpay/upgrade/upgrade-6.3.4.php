<?php

use BTCPay\Constants;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay|ModuleInterface|mixed $module
 *
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_6_3_4(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	// The "BTCPay Server unreachable" window starts closed
	Configuration::updateGlobalValue(Constants::CONFIGURATION_UNREACHABLE_UNTIL, 0);

	// A failed/expired invoice now moves the order on to the PrestaShop "Canceled" state, which puts the products back
	// in stock and emails the customer: the "Crypto transaction failed" state must not count as a sale nor send its own email
	$orderState = new OrderState((int) Configuration::get(Constants::CONFIGURATION_ORDER_STATE_FAILED));
	if (\Validate::isLoadedObject($orderState)) {
		$orderState->logable    = false;
		$orderState->send_email = false;
		$orderState->save();
	}

	// Host, API key, store and webhook are left untouched
	return true;
}
