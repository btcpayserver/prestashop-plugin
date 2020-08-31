<?php

namespace BTCPay\Server;

use Address;
use BTCPay\Exception\BTCPayException;
use BTCPay\LegacyOrderBitcoinRepository;
use BTCPayServer\Buyer;
use BTCPayServer\Currency as BTCPayCurrency;
use BTCPayServer\Invoice;
use BTCPayServer\Item;
use Customer;
use State;

class Factory
{
	/**
	 * @var LegacyOrderBitcoinRepository
	 */
	private $repository;

	/**
	 * @var \Link
	 */
	private $link;

	/**
	 * @var string
	 */
	private $moduleName;

	public function __construct(LegacyOrderBitcoinRepository $repository, \Link $link, string $moduleName)
	{
		$this->repository = $repository;
		$this->link       = $link;
		$this->moduleName = $moduleName;
	}

	public function createPaymentRequest(\Customer $customer, \Cart $cart): ?string
	{
		// Check if we have a cart ID we can use
		if (empty($cart->id)) {
			\PrestaShopLogger::addLog(
				'[Error] The BTCPay payment plugin was called to process a payment but the cart ID was missing.',
				3
			);

			return null;
		}

		// Build the client from our stored configuration
		try {
			$client = Client::createFromConfiguration();
		} catch (\Exception $e) {
			\PrestaShopLogger::addLog('[ERROR] ' . $e->getMessage(), $e->getCode());
			throw new BTCPayException($e->getMessage(), $e->getCode(), $e);
		}

		// If another BTCPay invoice was created before, returns the original one
		if (null !== ($redirect = $client->getBTCPayRedirect($cart))) {
			\PrestaShopLogger::addLog(
				'[WARNING] Existing BTCPay invoice has already been created, redirecting to it...',
				2
			);

			return $redirect;
		}

		// Setup the Invoice
		$invoice = new Invoice();
		$invoice->setFullNotifications(true);
		$invoice->setExtendedNotifications(true);

		// Get and set transaction speed
		if (empty($transactionSpeed = \Configuration::get('BTCPAY_TXSPEED'))) {
			$transactionSpeed = 'default';
		}

		$invoice->setTransactionSpeed($transactionSpeed);

		// Set an order reference instead of ID, since we haven't made one yet
		$invoiceReference = \Tools::passwdGen(20);
		$invoice->setOrderId($invoiceReference);

		// Get shopping currency, currently tested with be EUR
		$currency       = \Currency::getCurrencyInstance((int) $cart->id_currency);
		$btcpayCurrency = new BTCPayCurrency($currency->iso_code);
		$invoice->setCurrency($btcpayCurrency);

		// Add a basic item to our invoice
		$orderTotal = $cart->getOrderTotal(true);
		$invoice->setItem(
			(new Item())
				->setCode('Cart: ' . $cart->id)
				->setDescription('Your purchase')
				->setPrice($orderTotal)
		);

		// Set POS data so we can verify later on that the call is legit
		$invoice->setPosData($customer->secure_key);

		// Create a buyer we can add to the invoice
		$buyer = new Buyer();

		// Add customer information to buyer
		$customer = new Customer((int) $cart->id_customer);
		$buyer->setEmail($customer->email);
		$buyer->setFirstName($customer->firstname);
		$buyer->setLastName($customer->lastname);

		// Add address information to buyer
		$address = new Address($cart->id_address_delivery);
		$buyer->setAddress([$address->address1, $address->address2]);
		$buyer->setCountry($address->country);
		$buyer->setZip($address->postcode);
		$buyer->setCity($address->city);

		// Add the state if available
		if (0 !== ($stateId = $address->id_state)) {
			$buyer->setState((new State($stateId))->name);
		}

		// Finally, add the build buyer to the invoice
		$invoice->setBuyer($buyer);

		// Create the redirect URL once payment has been done.
		$invoice->setRedirectUrl($this->link->getModuleLink($this->moduleName, 'validation', ['invoice_reference' => $invoiceReference], true));

		// This is the callback url for invoice paid.
		$invoice->setNotificationUrl($this->link->getModuleLink($this->moduleName, 'ipn', [], true));

		// Ask BTCPay to create an invoice with cart information
		try {
			$errorReporting = error_reporting();
			error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_WARNING);
			$invoice = $client->createInvoice($invoice);
			error_reporting($errorReporting);

			\PrestaShopLogger::addLog('Invoice ' . $invoice->getId() . ' created, see ' . $invoice->getUrl(), 2);

			// Register invoice into bitcoin_payment table, if we didn't have one before.
			if (null === ($orderBitcoin = $this->repository->getOneByCartID($cart->id))) {
				$orderBitcoin = $this->repository->create($cart->id, (string) \Configuration::get('BTCPAY_OS_WAITING'), $invoice->getId());
			}

			$orderBitcoin->setInvoiceId($invoice->getId());
			$orderBitcoin->setInvoiceReference($invoiceReference);
			$orderBitcoin->setAmount((string) $orderTotal);
			$orderBitcoin->setRedirect($invoice->getUrl());

			$response = json_decode($client->getResponse()->getBody(), false);
			$orderBitcoin->setRate($response->data->rate);
			$orderBitcoin->setBitcoinPrice($response->data->btcPrice);
			$orderBitcoin->setBitcoinPaid($response->data->btcPaid);
			$orderBitcoin->setBitcoinAddress($response->data->bitcoinAddress);

			if (false === $orderBitcoin->save(true)) {
				\PrestaShopLogger::addLog('[ERROR] Could not store bitcoin_payment', 3);

				throw new \RuntimeException('[ERROR] Could not store bitcoin_payment');
			}

			\PrestaShopLogger::addLog('Invoice ' . $invoice->getId() . ' updated', 2);

			return $orderBitcoin->getRedirect();
		} catch (\Exception $e) {
			\PrestaShopLogger::addLog('[ERROR] ' . $e->getMessage(), 3);
			throw new BTCPayException($e->getMessage(), $e->getCode(), $e);
		}
	}
}
