<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\LegacyBitcoinPaymentRepository;
use BTCPayServer\Client\AbstractClient;
use BTCPayServer\Client\ApiKey as ApiKeyClient;
use BTCPayServer\Client\Invoice as InvoiceClient;
use BTCPayServer\Client\Server as ServerClient;
use BTCPayServer\Client\Store as StoreClient;
use BTCPayServer\Client\StorePaymentMethod;
use BTCPayServer\Client\StorePaymentMethodLightningNetwork;
use BTCPayServer\Client\StorePaymentMethodOnChain;
use PrestaShop\PrestaShop\Adapter\Configuration;

class Client extends AbstractClient
{
	/**
	 * @var InvoiceClient
	 */
	private $invoice;

	/**
	 * @var ApiKeyClient
	 */
	private $apiKey;

	/**
	 * @var ServerClient
	 */
	private $server;

	/**
	 * @var StoreClient
	 */
	private $store;

	/**
	 * @var StorePaymentMethod
	 */
	private $payment;

	/**
	 * @var StorePaymentMethodOnChain
	 */
	private $onChain;

	/**
	 * @var StorePaymentMethodLightningNetwork
	 */
	private $offChain;

	/**
	 * @var Webhook
	 */
	private $webhook;

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var LegacyBitcoinPaymentRepository
	 */
	private $repository;

	public function __construct(string $baseUrl, string $apiKey)
	{
		$httpClient = new CurlAdapter();

		parent::__construct($baseUrl, $apiKey, $httpClient);

		$this->apiKey   = new ApiKeyClient($baseUrl, $apiKey, $httpClient);
		$this->invoice  = new InvoiceClient($baseUrl, $apiKey, $httpClient);
		$this->server   = new ServerClient($baseUrl, $apiKey, $httpClient);
		$this->store    = new StoreClient($baseUrl, $apiKey, $httpClient);
		$this->payment  = new StorePaymentMethod($baseUrl, $apiKey, $httpClient);
		$this->onChain  = new StorePaymentMethodOnChain($baseUrl, $apiKey, $httpClient);
		$this->offChain = new StorePaymentMethodLightningNetwork($baseUrl, $apiKey, $httpClient);
		$this->webhook  = new Webhook($baseUrl, $apiKey, $httpClient);

		$this->configuration = new Configuration();
		$this->repository    = new LegacyBitcoinPaymentRepository();
	}

	public static function createFromConfiguration(Configuration $configuration): ?self
	{
		$host = $configuration->get(Constants::CONFIGURATION_BTCPAY_HOST);
		$apiKey = $configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY);

		// Cannot create a client, if we do not have valid configuration
		if (null === $host || null === $apiKey) {
			return null;
		}

		return new self($host, $apiKey);
	}

	public function invoice(): InvoiceClient
	{
		return $this->invoice;
	}

	public function apiKey(): ApiKeyClient
	{
		return $this->apiKey;
	}

	public function server(): ServerClient
	{
		return $this->server;
	}

	public function store(): StoreClient
	{
		return $this->store;
	}

	public function payment(): StorePaymentMethod
	{
		return $this->payment;
	}

	public function onChain(): StorePaymentMethodOnChain
	{
		return $this->onChain;
	}

	public function offChain(): StorePaymentMethodLightningNetwork
	{
		return $this->offChain;
	}

	public function webhook(): Webhook
	{
		return $this->webhook;
	}

	public function isValid(): bool
	{
		try {
			// Test the server connection
			$this->server()->getInfo();

			// Test the store connection
			$this->store()->getStore($this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID))->getName();
		} catch (\Throwable) {
			return false;
		}

		return true;
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	public function getBTCPayRedirect(\Cart $cart): ?string
	{
		// Check if we have a cart ID we can use
		if (empty($cart->id)) {
			return null;
		}

		if (null === ($bitcoinPayment = $this->repository->getOneByCartID($cart->id))) {
			return null;
		}

		if (empty($redirect = $bitcoinPayment->getRedirect())) {
			return null;
		}

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Check the invoice status
		$invoice = $this->invoice->getInvoice($storeID, $bitcoinPayment->getInvoiceId());
		if ($invoice->isInvalid() || $invoice->isExpired()) {
			return null;
		}

		return $redirect;
	}
}
