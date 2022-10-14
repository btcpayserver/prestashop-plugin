<?php

namespace BTCPay\Installer;

use BTCPay\Server\Client;
use PrestaShop\PrestaShop\Adapter\Configuration;

class Webhook
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
	public function uninstall(): array
	{
		// Remove the current webhook to prevent issues in the future
		if (false === (Client::createFromConfiguration($this->configuration)->webhook()->removeCurrent())) {
			return [
				[
					'key'        => 'Could not remove webhook from the server. Please double check it is actually gone.',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			];
		}

		return [];
	}
}
