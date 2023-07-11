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
	 * @var \BTCPay
	 */
	private $module;

	public function __construct(\BTCPay $module, \Context $context)
	{
		$this->repository    = new LegacyBitcoinPaymentRepository();
		$this->link          = new \Link();
		$this->configuration = new Configuration();
		$this->context       = $context;
		$this->module        = $module;
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	public function createPaymentRequest(\Customer $customer, \Cart $cart): ?string
	{
		// Check if we have a cart ID we can use
		if (empty($cart->id)) {
			\PrestaShopLogger::addLog('[ERROR] The BTCPay payment plugin was called to process a payment but the cart ID was missing.', \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return null;
		}

		// Build the client from our stored configuration
		$client = Client::createFromConfiguration($this->configuration);

		// Ensure the client is ready for use
		if (null === $client || false === $client->isValid()) {
			\PrestaShopLogger::addLog("[ERROR] The BTCPay payment plugin was called to process a payment but the client doesn't exist.", \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return null;
		}

		// Make sure we have a webhook, before we redirect anyone anywhere
		$client->webhook()->ensureWebhook($this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID));

		// If another BTCPay invoice was created before, returns the original one
		if (null !== ($redirect = $client->getBTCPayRedirect($cart))) {
			\PrestaShopLogger::addLog('[WARNING] Existing BTCPay invoice has already been created for this cart, redirecting...', \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, null, 'Cart', $cart->id);

			return $redirect;
		}

		try {
			// Setup some stuff
			$currency         = \Currency::getCurrencyInstance($cart->id_currency);
			$address          = new \Address($cart->id_address_delivery);
			$invoiceReference = \Tools::passwdGen(20);

			// Get totals
			$taxAmount  = (string) $cart->getOrderTotal(true, \Cart::ONLY_PRODUCTS) - $cart->getOrderTotal(false, \Cart::ONLY_PRODUCTS);
			$orderTotal = (string) $cart->getOrderTotal(true);

			// Setup custom checkout options, defaults get picked from store config.
			$checkoutOptions = new InvoiceCheckoutOptions();
			$checkoutOptions
				->setSpeedPolicy($this->configuration->get(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM))
				->setRedirectURL($this->link->getModuleLink($this->module->name, 'validation', ['invoice_reference' => $invoiceReference], true));

			// Get the store ID
			$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

			// Build the metadata
			$metadata = $this->createMetadata($customer, $address, $invoiceReference, $taxAmount);

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

			\PrestaShopLogger::addLog(\sprintf('[INFO] Invoice %s created, see %s', $invoiceId, $invoiceUrl));

			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING);

			// Register invoice into bitcoin_payment table, if we didn't have one before.
			if (null === ($bitcoinPayment = $this->repository->getOneByCartID($cart->id))) {
				$bitcoinPayment = $this->repository->create($cart->id, $orderStatus, $invoiceId);
			}

			$bitcoinPayment->setInvoiceId($invoiceId);
			$bitcoinPayment->setInvoiceReference($invoiceReference);
			$bitcoinPayment->setAmount($orderTotal);
			$bitcoinPayment->setRedirect($invoiceUrl);

			if (false === $bitcoinPayment->save(true)) {
				$error = \sprintf('[ERROR] Could not store bitcoin_payment: %s', \Db::getInstance()->getMsgError());
				\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

				throw new \RuntimeException($error);
			}

			// If we create the order after payment, do not make the order yet
			if (Constants::ORDER_MODE_AFTER === $this->configuration->get(Constants::CONFIGURATION_ORDER_MODE)) {
				// Update the object, so we can always validate it afterward
				if (false === $bitcoinPayment->update(true)) {
					$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
					\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

					throw new \RuntimeException($error);
				}

				\PrestaShopLogger::addLog(\sprintf('[INFO] Invoice %s has been updated', $invoiceId), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

				// Redirect user to payment
				return $bitcoinPayment->getRedirect();
			}

			\PrestaShopLogger::addLog(\sprintf('[INFO] Invoice %s has been updated, creating actual order', $invoiceId), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

			$this->module->validateOrder(
				$bitcoinPayment->getCartId(),
				$bitcoinPayment->getStatus(),
				$bitcoinPayment->getAmount(),
				$this->module->displayName, // BTCPay
				null, //message should be new Message
				[], // extra variables for mail
				null, //currency special
				false, // don't touch amount
				$customer->secure_key
			);

			// Get the new order ID
			$orderId = (int) \Order::getIdByCartId($bitcoinPayment->getCartId());

			$bitcoinPayment->setOrderId($orderId);
			$bitcoinPayment->setStatus($orderStatus);

			// Update the object
			if (false === $bitcoinPayment->update(true)) {
				$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
				\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

				throw new \RuntimeException($error);
			}

			// Redirect user to payment
			return $bitcoinPayment->getRedirect();
		} catch (\Throwable $e) {
			\PrestaShopLogger::addLog(\sprintf('[ERROR] %s', $e->getMessage()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			throw new BTCPayException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	private function createMetadata(\Customer $customer, \Address $address, string $invoiceReference, string $taxAmount): array
	{
		$metadata = [
			'posData'     => $customer->secure_key,
			'itemCode'    => \sprintf('invoice-reference-%s', $invoiceReference),
			'itemDesc'    => \sprintf('Purchase from %s', $this->context->shop->name),
			'taxIncluded' => $taxAmount,
		];

		// Only include personal details if enabled, if not, return here
		if (false === (bool) $this->configuration->get(Constants::CONFIGURATION_SHARE_METADATA, false)) {
			return $metadata;
		}

		$metadata = \array_merge($metadata, [
			'buyerName'     => \sprintf('%s %s', $customer->firstname, $customer->lastname),
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

		return $metadata;
	}
}
