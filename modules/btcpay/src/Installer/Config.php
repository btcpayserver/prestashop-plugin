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
		// Ensure sane defaults
		if (!$this->configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, Constants::CONFIGURATION_DEFAULT_HOST)
			|| !$this->configuration->set(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM)
			|| !$this->configuration->set(Constants::CONFIGURATION_ORDER_MODE, Constants::ORDER_MODE_BEFORE)
			|| !$this->configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, null)
			|| !$this->configuration->set(Constants::CONFIGURATION_BTCPAY_STORE_ID, null)
			|| !$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID, null)
			|| !$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET, null)
			|| !$this->configuration->set(Constants::CONFIGURATION_SHARE_METADATA, false)) {
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
			|| !$this->configuration->remove(Constants::CONFIGURATION_SPEED_MODE)
			|| !$this->configuration->remove(Constants::CONFIGURATION_ORDER_MODE)
			|| !$this->configuration->remove(Constants::CONFIGURATION_BTCPAY_API_KEY)
			|| !$this->configuration->remove(Constants::CONFIGURATION_BTCPAY_STORE_ID)
			|| !$this->configuration->remove(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID)
			|| !$this->configuration->remove(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET)
			|| !$this->configuration->remove(Constants::CONFIGURATION_SHARE_METADATA)) {
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
