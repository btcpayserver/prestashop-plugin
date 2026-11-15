<?php

declare(strict_types=1);

use BTCPay\Constants;
use BTCPay\Installer\OrderStates;
use BTCPay\Repository\TableRepository;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\ModuleInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

/**
 * One upgrade from any earlier module version onto the 7.0 schema.
 *
 * @param BTCPay|ModuleInterface|mixed $module
 */
function upgrade_module_7_0_0(mixed $module): bool
{
	if (!$module instanceof BTCPay) {
		throw new LogicException('Received invalid module');
	}

	if (\version_compare(\PHP_VERSION, Constants::MINIMUM_PHP_VERSION, '<')) {
		$module->_errors[] = \sprintf('PHP version is too low. Expected %s or higher, received %s.', Constants::MINIMUM_PHP_VERSION, \PHP_VERSION);

		return false;
	}

	if (\version_compare(_PS_VERSION_, Constants::MINIMUM_PS_VERSION, '<')) {
		$module->_errors[] = \sprintf('PrestaShop version is too low. Expected %s or higher, received %s.', Constants::MINIMUM_PS_VERSION, _PS_VERSION_);

		return false;
	}

	$repository = new TableRepository($module->get('doctrine.dbal.default_connection'));
	if ([] !== $repository->createTables() || [] !== $repository->dropLegacyColumns() || [] !== $repository->convertToUtf8mb4()) {
		return false;
	}

	if (!$module->unregisterHook('payment') || !$module->unregisterHook('displayPaymentEU')) {
		return false;
	}

	$orderStates = new OrderStates(
		$module->name,
		$module->get(CommandBusInterface::class),
		$module->get(ShopConfigurationInterface::class),
	);
	if ([] !== $orderStates->install()) {
		return false;
	}

	return $orderStates->synchronizeExisting();
}
