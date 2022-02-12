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
	public function process(Request $request): void
	{
		$data = \json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
		if (false === $data || null === $data) {
			return;
		}

		// Check if we received an event
		if (!\array_key_exists('type', $data) || !\array_key_exists('invoiceId', $data)) {
			return;
		}

		// If it's a test, just accept it
		if (false !== \strpos($data['invoiceId'], '__test__')) {
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received test IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));

			return;
		}

		// Get the data type
		$dataType = (string) $data['type'];

		// Payment has been received
		if (\in_array($dataType, ['InvoiceProcessing', 'InvoicePaidInFull', 'InvoiceReceivedPayment'], true)) {
			$this->paymentReceived($data);

			return;
		}

		// Payment has failed
		if (\in_array($dataType, ['InvoiceInvalid', 'InvoiceExpired'], true)) {
			$this->paymentFailed($data);

			return;
		}

		// Payment has been confirmed
		if ('InvoiceSettled' === $dataType) {
			$this->paymentConfirmed($data);

			return;
		}

		// We don't really care about these events
		if (\in_array($dataType, ['InvoiceCreated', 'InvoicePaymentSettled'], true)) {
			return;
		}

		// Log other IPN's.
		\PrestaShopLogger::addLog('[INFO] Received IPN that we did not process IPN');
		\PrestaShopLogger::addLog(\sprintf('[INFO] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \JsonException
	 */
	private function paymentReceived(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(\sprintf('[INFO] Payment received for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			$error = \sprintf('[ERROR] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[ERROR] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, 4);

			// Don't bother retrying, Prestashop should have sent email
			return;
		}

		// Get the order
		$order = new \Order($bitcoinPayment->getOrderId());

		// waiting confirmation
		$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', 1, null, 'Order', $order->id);

			return;
		}

		// Add a message if the user paid too late
		if (\array_key_exists('afterExpiration', $data) && true === (bool) $data['afterExpiration']) {
			\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', 1, null, 'Order', $order->id);
		}

		// Add a message if the user paid too late
		if (\array_key_exists('overPaid', $data) && true === (bool) $data['overPaid']) {
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

		// Add the order change to the order history table
		$orderHistory           = new \OrderHistory();
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
	private function paymentFailed(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(\sprintf('[INFO] Payment failed for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			$error = \sprintf('[ERROR] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[ERROR] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, 4);

			// Don't bother retrying, Prestashop should have sent email
			return;
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
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', 1, null, 'Order', $order->id);

			return;
		}

		// Update the status
		$bitcoinPayment->setStatus((string) $orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
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
		\PrestaShopLogger::addLog(\sprintf('[INFO] Payment confirmed for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			$error = \sprintf('[ERROR] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[ERROR] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, 4);

			// Don't bother retrying, Prestashop should have sent email
			return;
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
		if (\array_key_exists('afterExpiration', $data) && true === (bool) $data['afterExpiration']) {
			\PrestaShopLogger::addLog('[INFO] User paid after expiration for this invoice', 1, null, 'Order', $order->id);
		}

		// Add a message if the overpaid
		if (\array_key_exists('overPaid', $data) && true === (bool) $data['overPaid']) {
			\PrestaShopLogger::addLog('[INFO] User overpaid for this order', 1, null, 'Order', $order->id);
		}

		if ($invoice->isMarked()) {
			// Transaction was marked completed via BTCPay Server
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog('[INFO] The state is the same as the one received', 1, null, 'Order', $order->id);

			return;
		}

		// Set the status
		$bitcoinPayment->setStatus((string) $orderStatus);

		// Update the object
		if (false === $bitcoinPayment->update(true)) {
			$error = \sprintf('[ERROR] Could not update bitcoin_payment: %s', \Db::getInstance()->getMsgError());
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
}
