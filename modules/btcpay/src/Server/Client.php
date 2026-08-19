<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\Invoice\CheckoutGuard;
use BTCPay\Repository\BitcoinPaymentRepository;
use BTCPayServer\Client\AbstractClient;
use BTCPayServer\Client\ApiKey as ApiKeyClient;
use BTCPayServer\Client\Invoice as InvoiceClient;
use BTCPayServer\Client\Server as ServerClient;
use BTCPayServer\Client\Store as StoreClient;
use BTCPayServer\Client\StorePaymentMethod;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;

if (!\defined('_PS_VERSION_')) {
	exit;
}

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
	 * @var Webhook
	 */
	private $webhook;

	/**
	 * @var Configuration
	 */
	private $configuration;

	public function __construct(string $baseUrl, string $apiKey)
	{
		$httpClient = new CurlAdapter();

		parent::__construct($baseUrl, $apiKey, $httpClient);

		$this->apiKey   = new ApiKeyClient($baseUrl, $apiKey, $httpClient);
		$this->invoice  = new InvoiceClient($baseUrl, $apiKey, $httpClient);
		$this->server   = new ServerClient($baseUrl, $apiKey, $httpClient);
		$this->store    = new StoreClient($baseUrl, $apiKey, $httpClient);
		$this->payment  = new StorePaymentMethod($baseUrl, $apiKey, $httpClient);
		$this->webhook  = new Webhook($baseUrl, $apiKey, $httpClient);

		$this->configuration = new Configuration();
	}

	public static function createFromConfiguration(ShopConfigurationInterface $configuration): ?self
	{
		$host = $configuration->get(Constants::CONFIGURATION_BTCPAY_HOST, null);
		$apiKey = $configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY, null);

		// Cannot create a client, if we do not have valid configuration
		if (empty($host) || empty($apiKey)) {
			return null;
		}

		return new self($host, $apiKey);
	}

	public function getBaseUrl(): string
	{
		return parent::getBaseUrl();
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

		if (null === ($bitcoinPayment = BitcoinPaymentRepository::getOneByCartID($cart->id))) {
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

		// Recreate the invoice when the cart total no longer matches the snapshot
		$orderTotal   = (string) $cart->getOrderTotal(true);
		$currency     = \Currency::getCurrencyInstance($cart->id_currency);
		$storedAmount = $bitcoinPayment->getAmount();
		if (null !== $storedAmount && '' !== $storedAmount && false === CheckoutGuard::amountsMatch($storedAmount, $orderTotal)) {
			return null;
		}

		// Cart currency must still match the BTCPay invoice
		$invoiceData = $invoice->getData();
		if (\is_array($invoiceData) && isset($invoiceData['currency']) && \strtoupper((string) $invoiceData['currency']) !== \strtoupper((string) $currency->iso_code)) {
			return null;
		}

		return $redirect;
	}
}
