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
function upgrade_module_6_1_0(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	// Set a sane default for the new protection mode (which is true)
	Configuration::set(Constants::CONFIGURATION_PROTECT_ORDERS, true);

	// Update "paid with crypto" order statuses to allow the invoice to be downloaded
	$orderState = new OrderState(Configuration::get(Constants::CONFIGURATION_ORDER_STATE_PAID));

	// Ensure we actually have a valid order state
	if (!\Validate::isLoadedObject($orderState)) {
		throw new \LogicException(\sprintf("Expected order state '%s' to exist", Constants::CONFIGURATION_ORDER_STATE_FAILED));
	}

	// Allow the invoice to be downloaded and save the order state
	$orderState->invoice = true;
	$orderState->save();

	// And we are done
	return true;
}
