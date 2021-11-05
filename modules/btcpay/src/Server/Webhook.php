<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\Exception\BTCPayException;
use BTCPayServer\Http\ClientInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Response;

class Webhook extends \BTCPayServer\Client\Webhook
{
	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var \Link
	 */
	private $link;

	public function __construct(string $baseUrl, string $apiKey, ClientInterface $client = null)
	{
		parent::__construct($baseUrl, $apiKey, $client);

		$this->configuration = new Configuration();
		$this->link          = new \Link();
	}

	/**
	 * @throws \JsonException
	 */
	public function getCurrent(string $storeId): ?\BTCPayServer\Result\Webhook
	{
		foreach ($this->getWebhooks($storeId) as $webhook) {
			if ($webhook->offsetGet('id') !== $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID)) {
				continue;
			}

			return $webhook;
		}

		return null;
	}

	/**
	 * @throws \JsonException
	 * @throws \Exception
	 */
	public function ensureWebhook(string $storeId): void
	{
		// Check if we have an existing webhook, if so, just cancel now
		foreach ($this->getWebhooks($storeId) as $webhook) {
			if ($webhook->offsetGet('id') === $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID)) {
				return;
			}
		}

		// Generate new webhook secret
		$secret = bin2hex(random_bytes(24));

		// Store the webhook secret we made
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET, $secret);

		$webhookURL = $this->link->getModuleLink('btcpay', 'webhook', [], true);
		$webhook    = $this->createWebhook($storeId, $webhookURL, null, $secret);
		if (!$webhook->offsetExists('id') || !$webhook->offsetExists('secret')) {
			throw new BTCPayException('Webhook wasn\'t created correctly.', Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		if ($webhook->offsetGet('secret') !== $secret) {
			throw new BTCPayException('Webhook secret doesn\'t match our secret.', Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		// Store the ID, so we can check if we already have a valid webhook
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID, $webhook->offsetGet('id'));
	}
}
