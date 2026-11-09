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
function upgrade_module_6_3_6(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	// Failed/expired invoices now move on to PrestaShop "Canceled" (restock + email)
	$orderState = new OrderState((int) Configuration::get(Constants::CONFIGURATION_ORDER_STATE_FAILED));
	if (\Validate::isLoadedObject($orderState)) {
		$orderState->logable    = false;
		$orderState->send_email = false;
		if (!$orderState->save()) {
			return false;
		}
	}

	return true;
}
