<?php

namespace BTCPay\Invoice;

use BTCPay\Constants;
use BTCPay\Entity\BitcoinPayment;
use BTCPay\Server\Client;
use PrestaShop\PrestaShop\Adapter\Configuration;

class Processor
{
	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var \BTCPay
	 */
	private $module;

	public function __construct(\BTCPay $module, Configuration $configuration, Client $client)
	{
		$this->configuration = $configuration;
		$this->client = $client;
		$this->module = $module;
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentConfirmed(BitcoinPayment $bitcoinPayment)
	{
		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Set the default status to be the current status
		$orderStatus = $order->current_state;

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Change state if it's processing
		if ($invoice->isProcessing()) {
			// Transaction received but we have to wait some confirmation
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		// Change state if it's settled
		if ($invoice->isSettled()) {
			// Transaction confirmed on the network
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// Transaction was marked completed via BTCPay Server
		if ($invoice->isMarked()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// Add a message if the user paid too late
		if ($invoice->isPaidLate()) {
			\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);
		}

		// Add a message if the overpaid
		if ($invoice->isOverpaid()) {
			\PrestaShopLogger::addLog('[INFO] User overpaid for this order', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);

			return;
		}

		// Set the status
		$bitcoinPayment->setStatus((string) $orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			throw new \RuntimeException($error);
		}

		// Add the order change to the order history table
		$orderHistory = new \OrderHistory();
		$orderHistory->id_order = $bitcoinPayment->getOrderId();

		// Store the change
		$orderHistory->changeIdOrderState($orderStatus, $bitcoinPayment->getOrderId(), true);
		$orderHistory->add(true);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentFailed(BitcoinPayment $bitcoinPayment)
	{
		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Set the default status to be the current status
		$orderStatus = $order->current_state;

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Change the order status if needed
		if ($invoice->isInvalid() || $invoice->isExpired()) {
			// Expiration for the invoice has passed, so mark it failed
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED);
		}

		// Transaction was marked completed via BTCPay Server
		if ($invoice->isMarked()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);

			return;
		}

		// Update the status
		$bitcoinPayment->setStatus((string) $orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			throw new \RuntimeException($error);
		}

		// Add the order change to the order history table
		$orderHistory = new \OrderHistory();
		$orderHistory->id_order = $bitcoinPayment->getOrderId();

		// Store the change
		$orderHistory->changeIdOrderState($orderStatus, $bitcoinPayment->getOrderId(), true);
		$orderHistory->add(true);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentReceived(BitcoinPayment $bitcoinPayment)
	{
		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Specify what status it will be
		$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);

			return;
		}

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Add a message if the user paid too late
		if ($invoice->isPaidLate()) {
			\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);
		}

		// Add a message if the overpaid
		if ($invoice->isOverpaid()) {
			\PrestaShopLogger::addLog('[INFO] User overpaid for this order', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);
		}

		// Update the status
		$bitcoinPayment->setStatus($orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			throw new \RuntimeException($error);
		}

		// Add the order change to the order history table
		$orderHistory = new \OrderHistory();
		$orderHistory->id_order = $bitcoinPayment->getOrderId();

		// Store the change
		$orderHistory->changeIdOrderState($orderStatus, $bitcoinPayment->getOrderId(), true);
		$orderHistory->add(true);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentReceivedCreateAfter(BitcoinPayment $bitcoinPayment)
	{
		// Specify what status it will be
		$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Fetch the secure key, which is used to check if the order has been made from this store
		if (null === ($invoiceData = $invoice->getData()) || !\array_key_exists('metadata', $invoiceData) || !\array_key_exists('posData', $invoiceData['metadata'])) {
			\PrestaShopLogger::addLog('[ERROR] Secure key was not defined', \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return;
		}

		// Grab the secure key
		$secureKey = $invoiceData['metadata']['posData'];

		// Generate an order only if there is not another one with this cart
		if ($existingOrder = \Order::getByCartId($bitcoinPayment->getCartId())) {
			$message = \sprintf('[INFO] Invoice %s already has an order %s', $bitcoinPayment->getInvoiceId(), $existingOrder->reference);
			\PrestaShopLogger::addLog($message, \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

			return;
		}

		$this->module->validateOrder(
			$bitcoinPayment->getCartId(),
			$orderStatus,
			$bitcoinPayment->getAmount(),
			$this->module->displayName, // BTCPay
			null, //message should be new Message
			[], // extra variables for mail
			null, //currency special
			false, // don't touch amount
			$secureKey
		);

		// Get the new order ID
		$order = \Order::getByCartId($bitcoinPayment->getCartId());

		$bitcoinPayment->setOrderId($order->id);
		$bitcoinPayment->setStatus($orderStatus);

		// Add a message if the user paid too late
		if ($invoice->isPaidLate()) {
			\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);
		}

		// Add a message if the overpaid
		if ($invoice->isOverpaid()) {
			\PrestaShopLogger::addLog('[INFO] User overpaid for this order', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);
		}

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			throw new \RuntimeException($error);
		}
	}
}
