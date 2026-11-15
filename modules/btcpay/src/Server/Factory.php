<?php

declare(strict_types=1);

namespace BTCPay\Server;

use Address;
use BTCPay;
use BTCPay\Constants;
use BTCPay\Exception\BTCPayException;
use BTCPay\Repository\BitcoinPaymentRepository;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use BTCPayServer\Util\PreciseNumber;
use Cart;
use Context;
use Currency;
use Customer;
use JsonException;
use Link;
use Order;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShopDatabaseException;
use PrestaShopException;
use PrestaShopLogger;
use State;
use Throwable;
use Tools;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class Factory
{
	public function __construct(
		private readonly BTCPay $module,
		private readonly ShopConfigurationInterface $configuration,
		private readonly BitcoinPaymentRepository $payments,
	) {
	}

	/**
	 * @throws PrestaShopDatabaseException
	 * @throws JsonException
	 */
	public function createPaymentRequest(Customer $customer, Cart $cart): ?string
	{
		// Check if we have a cart ID we can use
		if (empty($cart->id)) {
			PrestaShopLogger::addLog('[ERROR] The BTCPay payment plugin was called to process a payment but the cart ID was missing.', PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return null;
		}

		// Build the client from our stored configuration
		$client = Client::createFromConfiguration($this->configuration);

		// Ensure the client is ready for use
		if (null === $client || false === $client->isValid()) {
			PrestaShopLogger::addLog("[ERROR] The BTCPay payment plugin was called to process a payment but the client doesn't exist.", PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return null;
		}

		// Make sure we have a webhook, before we redirect anyone anywhere
		$client->webhook()->ensureWebhook($this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID));

		// If another BTCPay invoice was created before, returns the original one
		if (null !== ($redirect = $client->getBTCPayRedirect($cart, $this->payments))) {
			PrestaShopLogger::addLog('[WARNING] Existing BTCPay invoice has already been created for this cart, redirecting...', PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, null, 'Cart', $cart->id);

			return $redirect;
		}

		try {
			// Setup some stuff
			$currency = Currency::getCurrencyInstance($cart->id_currency);
			$address = new Address($cart->id_address_delivery);
			$invoiceReference = Tools::passwdGen(20);

			// Get totals
			$taxAmount = (string) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS) - $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
			$orderTotal = (string) $cart->getOrderTotal(true);

			// Setup custom checkout options, defaults get picked from store config.
			$checkoutOptions = new InvoiceCheckoutOptions();
			$checkoutOptions
				->setSpeedPolicy($this->configuration->get(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM))
				->setRedirectURL((new Link())->getModuleLink($this->module->name, 'validation', ['invoice_reference' => $invoiceReference], true));

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
			$invoiceId = $invoiceResponse['id'];
			$invoiceUrl = $invoiceResponse['checkoutLink'];

			PrestaShopLogger::addLog(\sprintf('[INFO] Invoice %s created, see %s', $invoiceId, $invoiceUrl));

			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING);

			// Register invoice into bitcoin_payment table, if we didn't have one before.
			if (null === ($bitcoinPayment = $this->payments->findOneByCartId((int) $cart->id))) {
				$bitcoinPayment = $this->payments->create((int) $cart->id, $orderStatus, $invoiceId);
			}

			$bitcoinPayment->setInvoiceId($invoiceId);
			$bitcoinPayment->setInvoiceReference($invoiceReference);
			$bitcoinPayment->setAmount($orderTotal);
			$bitcoinPayment->setRedirect($invoiceUrl);
			$this->payments->save($bitcoinPayment);

			// If we create the order after payment, do not make the order yet
			if (Constants::ORDER_MODE_AFTER === $this->configuration->get(Constants::CONFIGURATION_ORDER_MODE)) {
				PrestaShopLogger::addLog(\sprintf('[INFO] Invoice %s has been updated', $invoiceId), PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

				// Redirect user to payment
				return $bitcoinPayment->getRedirect();
			}

			PrestaShopLogger::addLog(\sprintf('[INFO] Invoice %s has been updated, creating actual order', $invoiceId), PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

			$this->module->validateOrder(
				$bitcoinPayment->getCartId(),
				$bitcoinPayment->getStatus(),
				$bitcoinPayment->getAmount(),
				$this->module->displayName,
				null,
				[],
				null,
				false,
				$customer->secure_key,
				Context::getContext()->shop
			);

			// Get the new order ID
			$orderId = (int) Order::getIdByCartId($bitcoinPayment->getCartId());

			$bitcoinPayment->setOrderId($orderId);
			$bitcoinPayment->setStatus($orderStatus);

			// Update the object
			$this->payments->save($bitcoinPayment);

			// Redirect user to payment
			return $bitcoinPayment->getRedirect();
		} catch (Throwable $throwable) {
			PrestaShopLogger::addLog(\sprintf('[ERROR] %s', $throwable->getMessage()), PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, $throwable->getCode());

			throw new BTCPayException($throwable->getMessage(), $throwable->getCode(), $throwable);
		}
	}

	/**
	 * @throws PrestaShopDatabaseException
	 * @throws PrestaShopException
	 */
	private function createMetadata(Customer $customer, Address $address, string $invoiceReference, string $taxAmount): array
	{
		$metadata = [
			'posData' => $customer->secure_key,
			'itemCode' => \sprintf('invoice-reference-%s', $invoiceReference),
			'itemDesc' => \sprintf('Purchase from %s', Context::getContext()->shop->name),
			'taxIncluded' => $taxAmount,
		];

		// Only include personal details if enabled, if not, return here (force to bool, as the default could be not bool)
		if (false === (bool) $this->configuration->get(Constants::CONFIGURATION_SHARE_METADATA, false)) {
			return $metadata;
		}

		$metadata = \array_merge($metadata, [
			'buyerName' => \sprintf('%s %s', $customer->firstname, $customer->lastname),
			'buyerAddress1' => $address->address1,
			'buyerAddress2' => $address->address2,
			'buyerCity' => $address->city,
			'buyerZip' => $address->postcode,
			'buyerCountry' => $address->country,
		]);

		// Set state if available
		if (0 !== ($stateId = $address->id_state)) {
			$metadata['buyerState'] = (new State($stateId))->name;
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
