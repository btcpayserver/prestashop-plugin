<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\Exception\BTCPayException;
use BTCPay\LegacyBitcoinPaymentRepository;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use BTCPayServer\Util\PreciseNumber;
use PrestaShop\PrestaShop\Adapter\Configuration;

class Factory
{
	/**
	 * @var LegacyBitcoinPaymentRepository
	 */
	private $repository;

	/**
	 * @var \Link
	 */
	private $link;

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var \Context
	 */
	private $context;

	/**
	 * @var string
	 */
	private $moduleName;

	public function __construct(\Context $context, string $moduleName)
	{
		$this->repository    = new LegacyBitcoinPaymentRepository();
		$this->link          = new \Link();
		$this->configuration = new Configuration();
		$this->context       = $context;
		$this->moduleName    = $moduleName;
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	public function createPaymentRequest(\Customer $customer, \Cart $cart): ?string
	{
		// Check if we have a cart ID we can use
		if (empty($cart->id)) {
			\PrestaShopLogger::addLog('[ERROR] The BTCPay payment plugin was called to process a payment but the cart ID was missing.', 3);

			return null;
		}

		// Build the client from our stored configuration
		try {
			$client = Client::createFromConfiguration($this->configuration);
		} catch (\Throwable $e) {
			\PrestaShopLogger::addLog(sprintf('[ERROR] %s', $e->getMessage()), 4, $e->getCode());
			throw new BTCPayException($e->getMessage(), $e->getCode(), $e);
		}

		// If another BTCPay invoice was created before, returns the original one
		if (null !== ($redirect = $client->getBTCPayRedirect($cart))) {
			\PrestaShopLogger::addLog('[WARNING] Existing BTCPay invoice has already been created, redirecting to it...', 2);

			return $redirect;
		}

		try {
			// Setup some stuff
			$currency         = \Currency::getCurrencyInstance($cart->id_currency);
			$address          = new \Address($cart->id_address_delivery);
			$invoiceReference = \Tools::passwdGen(20);

			// Get totals
			$taxAmount  = $cart->getOrderTotal(true, \Cart::ONLY_PRODUCTS) - $cart->getOrderTotal(false, \Cart::ONLY_PRODUCTS);
			$orderTotal = (string) $cart->getOrderTotal(true);

			$metadata = [
				'posData'     => $customer->secure_key,
				'itemCode'    => sprintf('invoice-reference-%s', $invoiceReference),
				'itemDesc'    => sprintf('Purchase from %s', $this->context->shop->name),
				'taxIncluded' => $taxAmount,
			];

			// Only include personal details if enabled
			if (false !== (bool) $this->configuration->get(Constants::CONFIGURATION_SHARE_METADATA, false)) {
				$metadata = array_merge($metadata, [
					'buyerName'     => sprintf('%s %s', $customer->firstname, $customer->lastname),
					'buyerAddress1' => $address->address1,
					'buyerAddress2' => $address->address2,
					'buyerCity'     => $address->city,
					'buyerZip'      => $address->postcode,
					'buyerCountry'  => $address->country,
				]);

				// Set state if available
				if (0 !== ($stateId = $address->id_state)) {
					$metadata['buyerState'] = (new \State($stateId))->name;
				}

				// Set phone/mobile phone number if available
				if (!empty($address->phone)) {
					$metadata['buyerPhone'] = $address->phone;
				} elseif (!empty($address->phone_mobile)) {
					$metadata['buyerPhone'] = $address->phone_mobile;
				}
			}

			// Setup custom checkout options, defaults get picked from store config.
			$checkoutOptions = new InvoiceCheckoutOptions();
			$checkoutOptions
				->setSpeedPolicy($this->configuration->get(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM))
				->setRedirectURL($this->link->getModuleLink($this->moduleName, 'validation', ['invoice_reference' => $invoiceReference], true));

			// Get the store ID
			$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

			// Ask BTCPay to create an invoice with cart information
			$invoice = $client->invoice()->createInvoice(
				$storeID,
				$currency->iso_code,
				PreciseNumber::parseString($orderTotal),
				$invoiceReference,
				$customer->email,
				$metadata,
				$checkoutOptions
			);

			// Process response
			$invoiceResponse = $invoice->getData();
			$invoiceId       = $invoiceResponse['id'];
			$invoiceUrl      = $invoiceResponse['checkoutLink'];

			\PrestaShopLogger::addLog(sprintf('[INFO] Invoice %s created, see %s', $invoiceId, $invoiceUrl));

			// Register invoice into bitcoin_payment table, if we didn't have one before.
			if (null === ($bitcoinPayment = $this->repository->getOneByCartID($cart->id))) {
				$bitcoinPayment = $this->repository->create($cart->id, $this->configuration->getInt(Constants::CONFIGURATION_ORDER_STATE_WAITING), $invoiceId);
			}

			$bitcoinPayment->setInvoiceId($invoiceId);
			$bitcoinPayment->setInvoiceReference($invoiceReference);
			$bitcoinPayment->setAmount($orderTotal);
			$bitcoinPayment->setRedirect($invoiceUrl);

			if (false === $bitcoinPayment->save(true)) {
				\PrestaShopLogger::addLog('[ERROR] Could not store bitcoin_payment', 3);

				throw new \RuntimeException('[ERROR] Could not store bitcoin_payment');
			}

			\PrestaShopLogger::addLog(sprintf('[INFO] Invoice %s has been updated', $invoiceId));

			return $bitcoinPayment->getRedirect();
		} catch (\Throwable $e) {
			\PrestaShopLogger::addLog(sprintf('[ERROR] %s', $e->getMessage()), 3);
			throw new BTCPayException($e->getMessage(), $e->getCode(), $e);
		}
	}
}
