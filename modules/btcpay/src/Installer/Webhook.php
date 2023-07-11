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
		// Build the client from our stored configuration
		$client = Client::createFromConfiguration($this->configuration);

		// If there is no client, return now
		if (null === $client) {
			return [];
		}

		// Remove the current webhook to prevent issues in the future.
		if (false === ($client->webhook()->removeCurrent())) {
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
