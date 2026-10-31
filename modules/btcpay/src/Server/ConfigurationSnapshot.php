<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Captures the complete BTCPay configuration so a failed settings update can
 * restore the exact previous state instead of leaving a half-applied
 * combination behind (e.g. a new host paired with the previous API key and store).
 */
class ConfigurationSnapshot
{
	/**
	 * @var mixed
	 */
	private $host;

	/**
	 * @var mixed
	 */
	private $apiKey;

	/**
	 * @var mixed
	 */
	private $storeId;

	/**
	 * @var mixed
	 */
	private $webhookId;

	/**
	 * @var mixed
	 */
	private $webhookSecret;

	public static function capture(ShopConfigurationInterface $configuration): self
	{
		$snapshot              = new self();
		$snapshot->host        = $configuration->get(Constants::CONFIGURATION_BTCPAY_HOST);
		$snapshot->apiKey      = $configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY);
		$snapshot->storeId     = $configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);
		$snapshot->webhookId   = $configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID);
		$snapshot->webhookSecret = $configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET);

		return $snapshot;
	}

	/**
	 * Writes the captured values back, restoring the exact previous configuration.
	 */
	public function restore(ShopConfigurationInterface $configuration): void
	{
		$configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, $this->host);
		$configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, $this->apiKey);
		$configuration->set(Constants::CONFIGURATION_BTCPAY_STORE_ID, $this->storeId);
		$configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_ID, $this->webhookId);
		$configuration->set(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET, $this->webhookSecret);
	}

	/**
	 * @return mixed
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @return mixed
	 */
	public function getApiKey()
	{
		return $this->apiKey;
	}

	/**
	 * @return mixed
	 */
	public function getStoreId()
	{
		return $this->storeId;
	}

	/**
	 * @return mixed
	 */
	public function getWebhookId()
	{
		return $this->webhookId;
	}
}
