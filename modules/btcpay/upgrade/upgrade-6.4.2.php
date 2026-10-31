<?php

use BTCPay\Repository\TableRepository;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * @param BTCpay|ModuleInterface|mixed $module
 *
 * @throws JsonException
 */
function upgrade_module_6_4_2(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	// Add the webhook delivery tracking table
	$repository = new TableRepository($module->get('doctrine.dbal.default_connection'));

	return empty($repository->createTables());
}
