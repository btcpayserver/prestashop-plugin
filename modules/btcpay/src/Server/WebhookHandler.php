<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\LegacyBitcoinPaymentRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Request;

class WebhookHandler
{
	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var LegacyBitcoinPaymentRepository
	 */
	private $repository;

	/**
	 * @var Configuration
	 */
	private $configuration;

	public function __construct(Client $client, LegacyBitcoinPaymentRepository $repository)
	{
		$this->client        = $client;
		$this->repository    = $repository;
		$this->configuration = new Configuration();
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 * @throws \PrestaShopException
	 */
	public function process(\BTCPay $btcpay, Request $request): void
	{
		$data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
		if (false === $data || null === $data) {
			return;
		}

		// Check if we received an event
		if (!\array_key_exists('type', $data) || !\array_key_exists('invoiceId', $data)) {
			return;
		}

		// If it's a test, just accept it
		if (false !== strpos($data['invoiceId'], '__test__')) {
			\PrestaShopLogger::addLog(sprintf('[INFO] Received test IPN: %s', json_encode($data, \JSON_THROW_ON_ERROR)));

			return;
		}

		// Get order mode
		$orderMode = $this->configuration->get(Constants::CONFIGURATION_ORDER_MODE);

		// Invoice created - Before
		if ('InvoiceCreated' === $data['type'] && Constants::ORDER_MODE_BEFORE === $orderMode) {
			$this->invoiceCreated($data, $btcpay);

			return;
		}

		// Invoice created - After
		if ('InvoiceCreated' === $data['type'] && Constants::ORDER_MODE_AFTER === $orderMode) {
			\PrestaShopLogger::addLog(sprintf('[INFO] Received InvoiceCreated event, but not creating order because order mode is %s', Constants::ORDER_MODE_AFTER));

			return;
		}

		// Payment Received - Before
		if (Constants::ORDER_MODE_BEFORE === $orderMode && \in_array($data['type'], ['InvoiceProcessing', 'InvoicePaidInFull', 'InvoiceReceivedPayment'])) {
			$this->receivedPaymentBefore($data);

			return;
		}

		// Payment Received - After
		if (Constants::ORDER_MODE_AFTER === $orderMode && \in_array($data['type'], ['InvoiceProcessing', 'InvoicePaidInFull', 'InvoiceReceivedPayment'])) {
			$this->receivedPaymentAfter($data, $btcpay);

			return;
		}

		// Payment failed
		if (\in_array($data['type'], ['InvoiceInvalid', 'InvoiceExpired'])) {
			$this->failedPayment($data);

			return;
		}

		// Payment confirmed
		if ('InvoiceSettled' === $data['type']) {
			$this->paymentConfirmed($data);

			return;
		}

		// We don't really care about these events
		if ('InvoicePaymentSettled' === $data['type']) {
			return;
		}

		// Log other IPN's.
		\PrestaShopLogger::addLog('[ERROR] Could not process IPN', 3);
		\PrestaShopLogger::addLog(sprintf('[ERROR] Received IPN: %s', json_encode($data, \JSON_THROW_ON_ERROR)), 3);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \JsonException
	 */
	private function invoiceCreated(array $data, \BTCPay $btcpay): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(sprintf('[INFO] Received event to create order created for %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[ERROR] Could not load order', 3);

			throw new \RuntimeException('[ERROR] Could not load order');
		}

		// Get the order status for awaiting payment
		$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING);

		// On the  Order, just say payment processor is BTCPay
		$displayName = $btcpay->displayName;

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $invoiceId);

		// Fetch the secure key, used to check if the order from your prestashop
		if (null === ($invoiceData = $invoice->getData()) || !array_key_exists('metadata', $invoiceData) || !array_key_exists('posData', $invoiceData['metadata'])) {
			\PrestaShopLogger::addLog('[ERROR] No securekey', 3);
			throw new \RuntimeException('[ERROR] No securekey');
		}

		// Grab the secure key
		$secure_key = $invoiceData['metadata']['posData'];

		// generate an order only if their is not another one with this cart
		$orderId = \Order::getIdByCartId($bitcoinPayment->getCartId());
		if (false === $orderId || 0 === $orderId) {
			$btcpay->validateOrder(
				$bitcoinPayment->getCartId(),
				$orderStatus,
				$bitcoinPayment->getAmount(),
				$displayName, //bitcoin btcpay
				null, //message should be new Message
				[], //extravars for mail
				null, //currency special
				false, // don't touch amount
				$secure_key
			);

			// Get the new order ID
			$orderId = (int) \Order::getIdByCartId($bitcoinPayment->getCartId());

			$bitcoinPayment->setOrderId($orderId);
			$bitcoinPayment->setStatus($orderStatus);

			// Update the object
			if (false === $bitcoinPayment->update(true)) {
				$error = sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
				\PrestaShopLogger::addLog($error, 3);
				throw new \RuntimeException($error);
			}

			return;
		}

		// Order already created
		\PrestaShopLogger::addLog(sprintf('[WARNING] received event to create an order for invoice %s, but one already exists', $invoiceId), 2);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \JsonException
	 */
	private function receivedPaymentBefore(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(sprintf('[INFO] payment received for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[ERROR] Could not load order', 3);

			throw new \RuntimeException('[ERROR] Could not load order');
		}

		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// waiting confirmation
		$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] current state is not different than new order status in received payment', 1, null, 'Order', $order->id);

			return;
		}

		// Add a message if the user paid too late
		if (array_key_exists('afterExpiration', $data) && true === (bool) $data['afterExpiration']) {
			\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', 1, null, 'Order', $order->id);
		}

		// Add a message if the user paid too late
		if (array_key_exists('overPaid', $data) && true === (bool) $data['overPaid']) {
			\PrestaShopLogger::addLog('[INFO] User overpaid for this order', 1, null, 'Order', $order->id);
		}

		// Update the status
		$bitcoinPayment->setStatus($orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = '[ERROR] Could not update bitcoin_payment: ' . \Db::getInstance()->getMsgError();
			\PrestaShopLogger::addLog($error, 3);
			throw new \RuntimeException($error);
		}

		// add Order status change to Order history table
		$orderHistory           = new \OrderHistory();
		$orderHistory->id_order = $bitcoinPayment->getOrderId();

		// bitcoin confirmation ok
		$orderHistory->changeIdOrderState($orderStatus, $bitcoinPayment->getOrderId(), true);

		//add with email is mandatory to add new order state in order_history
		$orderHistory->add(true);
	}

	/**
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	private function receivedPaymentAfter(array $data, \BTCPay $btcpay): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog('[INFO] payment received for invoice ' . $invoiceId);

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[ERROR] Could not load order', 3);

			throw new \RuntimeException('[ERROR] Could not load order');
		}

		// waiting confirmation
		$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $invoiceId);

		// Fetch the secure key, used to check if the order from your prestashop
		if (null === ($invoiceData = $invoice->getData()) || !array_key_exists('metadata', $invoiceData) || !array_key_exists('posData', $invoiceData['metadata'])) {
			\PrestaShopLogger::addLog('[ERROR] No securekey', 3);
			throw new \RuntimeException('[ERROR] No securekey');
		}

		// Grab the secure key
		$secure_key = $invoiceData['metadata']['posData'];

		// generate an order only if their is not another one with this cart
		$orderId = \Order::getIdByCartId($bitcoinPayment->getCartId());
		if (false === $orderId || 0 === $orderId) {
			$btcpay->validateOrder(
				$bitcoinPayment->getCartId(),
				$orderStatus,
				$bitcoinPayment->getAmount(),
				$btcpay->displayName, //bitcoin btcpay
				null, //message
				[], //extravars
				null, //currency special
				false, // don't touch amount
				$secure_key
			);

			// Get the new order ID
			$orderId = (int) \Order::getIdByCartId($bitcoinPayment->getCartId());

			$bitcoinPayment->setOrderId($orderId);
			$bitcoinPayment->setStatus($orderStatus);

			// Add a message if the user paid too late
			if (array_key_exists('afterExpiration', $data) && true === (bool) $data['afterExpiration']) {
				\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', 1, null, 'Order', $orderId);
			}

			// Add a message if the overpaid
			if (array_key_exists('overPaid', $data) && true === (bool) $data['overPaid']) {
				\PrestaShopLogger::addLog('[INFO] User overpaid for this order', 1, null, 'Order', $orderId);
			}

			// Update the object
			if (false === $bitcoinPayment->update(true)) {
				$error = sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
				\PrestaShopLogger::addLog($error, 3);
				throw new \RuntimeException($error);
			}

			return;
		}

		// Order already paid
		\PrestaShopLogger::addLog('[ERROR] already paid order', 3);

		throw new \RuntimeException('[ERROR] already paid order');
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	private function failedPayment(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(sprintf('[INFO] payment failed for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[ERROR] Could not load order', 3);

			throw new \RuntimeException('[ERROR] Could not load order');
		}

		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Set the default status to be the current status
		$orderStatus = $order->current_state;

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $invoiceId);

		// Change the order status if needed
		if ($invoice->isInvalid() || $invoice->isExpired()) {
			// Expiration for the invoice has passed, so mark it failed
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED);
		}

		if ($invoice->isMarked()) {
			// Transaction was marked invalid via BTCPay Server
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] current state is not different than new order status in failed payment', 1, null, 'Order', $order->id);

			return;
		}

		// Update the status
		$bitcoinPayment->setStatus((string) $orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, 3);
			throw new \RuntimeException($error);
		}

		// Add the order change to the order history table
		$orderHistory           = new \OrderHistory();
		$orderHistory->id_order = $bitcoinPayment->getOrderId();

		// Store the change
		$orderHistory->changeIdOrderState($orderStatus, $bitcoinPayment->getOrderId(), true);
		$orderHistory->add(true);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 */
	private function paymentConfirmed(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(sprintf('[INFO] payment confirmed for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[ERROR] Could not load order', 3);

			throw new \RuntimeException('[ERROR] Could not load order');
		}

		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// Set the default status to be the current status
		$orderStatus = $order->current_state;

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $invoiceId);

		// Change state if it's paid/processing
		if ($invoice->isPaid() || $invoice->isProcessing()) {
			// Transaction received but we have to wait some confirmation
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		// Change state if it's fully paid
		if ($invoice->isFullyPaid()) {
			// Transaction confirmed on the network
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// Add a message if the user paid too late
		if (array_key_exists('afterExpiration', $data) && true === (bool) $data['afterExpiration']) {
			\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', 1, null, 'Order', $order->id);
		}

		// Add a message if the overpaid
		if (array_key_exists('overPaid', $data) && true === (bool) $data['overPaid']) {
			\PrestaShopLogger::addLog('[INFO] User overpaid for this order', 1, null, 'Order', $order->id);
		}

		if ($invoice->isMarked()) {
			// Transaction was marked completed via BTCPay Server
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] current state is not different than new order status in payment confirmed', 1, null, 'Order', $order->id);

			return;
		}

		// Set the status
		$bitcoinPayment->setStatus((string) $orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
			\PrestaShopLogger::addLog($error, 3);
			throw new \RuntimeException($error);
		}

		// Add the order change to the order history
		$orderHistory           = new \OrderHistory();
		$orderHistory->id_order = $bitcoinPayment->getOrderId();
		$orderHistory->changeIdOrderState($orderStatus, $bitcoinPayment->getOrderId(), true);
		$orderHistory->add(true);
	}
}
