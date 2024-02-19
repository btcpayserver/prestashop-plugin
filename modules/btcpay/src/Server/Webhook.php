<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\Exception\BTCPayException;
use BTCPayServer\Http\ClientInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Response;

if (!\defined('_PS_VERSION_')) {
	exit;
}

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
		$this->link = new \Link();
	}

	/**
	 * @throws \JsonException
	 * @throws \Exception
	 */
	public function ensureWebhook(string $storeId): void
	{
		// Check if we have an existing webhook, if so, just cancel now (empty check is required).
		if (false === empty($this->getCurrent($storeId, $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID)))) {
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

	public function getCurrent(string $storeId, ?string $webhookId): ?\BTCPayServer\Result\Webhook
	{
		try {
			// We need to check for empty here as twig passes a null variable as "" instead of null in configure.html.twig.
			if (empty($webhookId)) {
				return null;
			}

			if (null === ($webhook = $this->getWebhook($storeId, $webhookId))) {
				return null;
			}

			return !empty($webhook->getData()) ? $webhook : null;
		} catch (\Throwable $throwable) {
			$warning = \sprintf("[WARNING] expected webhook '%s' for store '%s' to exist, but it didn't. Exception received: %s", $webhookId, $storeId, $throwable->getMessage());
			\PrestaShopLogger::addLog($warning, \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, $throwable->getCode());

			return null;
		}
	}

	public function removeCurrent(): bool
	{
		if (empty($storeId = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID))) {
			return false;
		}

		if (empty($webhookId = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID))) {
			return false;
		}

		try {
			if (null === $this->getWebhook($storeId, $webhookId)) {
				return false;
			}

			$this->deleteWebhook($storeId, $webhookId);

			return true;
		} catch (\Throwable $throwable) {
			$message = \sprintf("[WARNING] Could not remove webhook '%s' from the store '%s'. Please double check it is actually gone. Exception received: %s", $webhookId, $storeId, $throwable->getMessage());
			\PrestaShopLogger::addLog($message, \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, $throwable->getCode());

			return false;
		}
	}
}
