<?php

namespace BTCPay\Invoice;

use BTCPay\Constants;
use BTCPay\Entity\BitcoinPayment;
use BTCPay\Repository\OrderPaymentRepository;
use BTCPay\Server\Client;
use BTCPayServer\Result\Invoice;
use PrestaShop\PrestaShop\Adapter\Configuration;

class Processor
{
	/**
	 * @var \BTCPay
	 */
	private $module;

	/**
	 * @var \Context
	 */
	private $context;

	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var Configuration
	 */
	private $configuration;

	public function __construct(\BTCPay $module, \Context $context, Configuration $configuration, Client $client)
	{
		$this->module        = $module;
		$this->context       = $context;
		$this->configuration = $configuration;
		$this->client        = $client;
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function invoiceSettled(BitcoinPayment $bitcoinPayment): void
	{
		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Set the default status to be the current status
		$orderStatus = $order->current_state;

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Ensure the invoice is not processing
		if ($invoice->isProcessing()) {
			\PrestaShopLogger::addLog(\sprintf("[ERROR] Invoice '%s' should not be processing when 'InvoiceSettled' has been received", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'Order', $order->id);

			return;
		}

		// Change state if it's settled
		if ($invoice->isSettled()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// Transaction was marked completed via BTCPay Server
		if ($invoice->isMarked()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);

			return;
		}

		// Store the updated status
		$this->updateOrderStatus($bitcoinPayment, $orderStatus);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function invoiceFailed(BitcoinPayment $bitcoinPayment): void
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

		// Transaction was marked as invalid via BTCPay Server
		if ($invoice->isMarked()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);

			return;
		}

		// Update the status
		$this->updateOrderStatus($bitcoinPayment, $orderStatus);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentReceived(BitcoinPayment $bitcoinPayment): void
	{
		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Set the default status to be the current status
		$orderStatus = $order->current_state;

		// If partially paid, we are still waiting for more
		if ($invoice->isPartiallyPaid()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING);
		}

		// Transaction received, but we have to wait some confirmation
		if ($invoice->isProcessing()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		// Transaction received, but paid late
		if ($invoice->isPaidLate()) {
			// Transaction received but we have to wait some confirmation
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		// Transaction received, but overpaid
		if ($invoice->isOverpaid()) {
			// Transaction received but we have to wait some confirmation
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		// Invoice confirmed on the network
		if ($invoice->isSettled()) {
			// Transaction received and already confirmed
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog(\sprintf("[INFO] The state is the same as the one received for invoice '%s'", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);

			return;
		}

		// Store the updated status
		$this->updateOrderStatus($bitcoinPayment, $orderStatus);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentReceivedCreateAfter(BitcoinPayment $bitcoinPayment): void
	{
		// Generate an order only if there is not another one with this cart
		if (null !== \Order::getByCartId($bitcoinPayment->getCartId())) {
			// We already have an order, so process as normal
			$this->paymentReceived($bitcoinPayment);

			return;
		}

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Set an initial state
		$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING);

		// If partially paid, we are still waiting for more
		if ($invoice->isPartiallyPaid()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING);
		}

		// Transaction received, but we have to wait some confirmation
		if ($invoice->isProcessing()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		// Transaction received, but paid late
		if ($invoice->isPaidLate()) {
			// Transaction received but we have to wait some confirmation
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);

			// Add a regular log
			\PrestaShopLogger::addLog(\sprintf("[INFO] User paid after expiration for this invoice '%s'", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE);
		}

		// Transaction received, but overpaid
		if ($invoice->isOverpaid()) {
			// Transaction received but we have to wait some confirmation
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		// Invoice confirmed on the network
		if ($invoice->isSettled()) {
			// Transaction received and already confirmed
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// Fetch the secure key, which is used to check if the order has been made from this store
		if (null === ($invoiceData = $invoice->getData()) || !\array_key_exists('metadata', $invoiceData) || !\array_key_exists('posData', $invoiceData['metadata'])) {
			\PrestaShopLogger::addLog('[ERROR] Secure key was not defined', \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return;
		}

		// Grab the secure key
		$secureKey = $invoiceData['metadata']['posData'];

		\PrestaShopLogger::addLog(\sprintf("[INFO] Creating actual order for invoice '%s'", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

		$this->module->validateOrder(
			$bitcoinPayment->getCartId(),
			$orderStatus,
			$bitcoinPayment->getAmount(),
			$this->module->displayName,
			null,
			[],
			null,
			false,
			$secureKey,
			$this->context->shop
		);

		// Get the new order ID
		$order = \Order::getByCartId($bitcoinPayment->getCartId());

		// Store the new order ID and set the proper status
		$bitcoinPayment->setOrderId($order->id);
		$bitcoinPayment->setStatus($orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			throw new \RuntimeException($error);
		}
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentSettled(BitcoinPayment $bitcoinPayment): void
	{
		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the payments from the server
		$paymentMethods = $this->client->invoice()->getPaymentMethods($storeID, $bitcoinPayment->getInvoiceId());

		// Process all payments
		foreach ($paymentMethods as $paymentMethod) {
			// Grab all payments per payment method
			$payments = $paymentMethod->getPayments();

			// Process all payments
			foreach ($payments as $payment) {
				// Payment is not yet settled, continue
				if (Invoice::STATUS_SETTLED !== $payment->getStatus()) {
					continue;
				}

				// Payment is known, continue
				if (OrderPaymentRepository::hasPayment($order, $payment)) {
					continue;
				}

				// Add the payment
				$order->addOrderPayment(\bcmul($payment->getValue(), $paymentMethod->getRate()), null, $payment->getTransactionId());
			}
		}
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	private function updateOrderStatus(BitcoinPayment $bitcoinPayment, string $orderStatus): void
	{
		// Set the status
		$bitcoinPayment->setStatus($orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			throw new \RuntimeException($error);
		}

		// Add the order change to the order history table
		$orderHistory           = new \OrderHistory();
		$orderHistory->id_order = $bitcoinPayment->getOrderId();

		// Store the change and make sure to create an invoice using existing payments (in case the status is changed to 'paid with crypto')
		$orderHistory->changeIdOrderState($orderStatus, $bitcoinPayment->getOrderId(), true);
		$orderHistory->add();
	}
}
