<?php

namespace BTCPay\Installer;

use BTCPay\Constants;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use PrestaShop\PrestaShop\Adapter\Configuration;

class Config
{
	/**
	 * @var Configuration
	 */
	private $configuration;

	public function __construct()
	{
		$this->configuration = new Configuration();
	}

	/**
	 * @throws \Exception
	 */
	public function install(): array
	{
		// Init clear configurations
		if (!$this->configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, Constants::CONFIGURATION_DEFAULT_HOST)
			|| !$this->configuration->set(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM)
			|| !$this->configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, null)) {
			return [
				[
					'key'        => 'Could not init configuration',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			];
		}

		return [];
	}

	/**
	 * @throws \Exception
	 */
	public function uninstall(): array
	{
		// Remove configuration
		if (!$this->configuration->remove(Constants::CONFIGURATION_BTCPAY_HOST)
			|| !$this->configuration->remove(Constants::CONFIGURATION_BTCPAY_API_KEY)
			|| !$this->configuration->remove(Constants::CONFIGURATION_SPEED_MODE)) {
			return [
				[
					'key'        => 'Could not clear configuration',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			];
		}

		return [];
	}
}
