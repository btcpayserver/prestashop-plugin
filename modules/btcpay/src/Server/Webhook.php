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
		foreach ($this->getStoreWebhooks($storeId)->all() as $webhook) {
			if ($webhook->getId() !== $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID)) {
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
		if (null !== $this->getCurrent($storeId)) {
			return;
		}

		// Generate new webhook secret
		$secret = \bin2hex(\random_bytes(24));

		// Build the webhook URL
		$webhookURL = $this->link->getModuleLink('btcpay', 'webhook', [], true);

		// Create the brand-new webhook
		$webhook = $this->createWebhook($storeId, $webhookURL, null, $secret);

		// Ensure we actually made a proper webhook
		if (empty($webhook->getId()) || empty($webhook->getSecret())) {
			throw new BTCPayException("Webhook wasn't created correctly.", Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		// Ensure the webhook was created with the secret we provided
		if ($webhook->getSecret() !== $secret) {
			throw new BTCPayException("Webhook secret doesn't match our secret.", Response::HTTP_INTERNAL_SERVER_ERROR);
		}

		// Store the webhook secret we made, so we can check that the webhook is actually made by us
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET, $secret);

		// Store the ID, so we can check if we already have a valid webhook
		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID, $webhook->getId());
	}
}
