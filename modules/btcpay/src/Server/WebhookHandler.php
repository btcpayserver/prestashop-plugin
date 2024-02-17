<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\Factory\CustomerMessage;
use BTCPay\Invoice\Processor;
use BTCPay\LegacyBitcoinPaymentRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Request;

class WebhookHandler
{
	/**
	 * @var \Context
	 */
	private $context;

	/**
	 * @var LegacyBitcoinPaymentRepository
	 */
	private $repository;

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var Processor
	 */
	private $processor;

	public function __construct(\BTCPay $module, \Context $context, Client $client, LegacyBitcoinPaymentRepository $repository)
	{
		$this->context       = $context;
		$this->repository    = $repository;
		$this->configuration = new Configuration();
		$this->processor     = new Processor($module, $context, $this->configuration, $client);
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
		if (\str_contains($data['invoiceId'], '__test__')) {
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received test IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));

			return;
		}

		// Get the data type
		$eventType = (string) $data['type'];

		// Get order mode
		$orderMode = $this->configuration->get(Constants::CONFIGURATION_ORDER_MODE);

		// Payment has been received (order already exists)
		if (Constants::ORDER_MODE_BEFORE === $orderMode && \in_array($eventType, ['InvoiceProcessing', 'InvoicePaidInFull', 'InvoiceReceivedPayment'], true)) {
			$this->paymentReceived($data);

			return;
		}

		// Payment has been received (order needs to be made)
		if (Constants::ORDER_MODE_AFTER === $orderMode && \in_array($eventType, ['InvoiceProcessing', 'InvoicePaidInFull', 'InvoiceReceivedPayment'], true)) {
			$this->paymentReceivedDelayedOrder($data);

			return;
		}

		// Payment has been confirmed
		if ('InvoicePaymentSettled' === $eventType) {
			$this->paymentSettled($data);

			return;
		}

		// Invoice has failed or expired
		if (\in_array($eventType, ['InvoiceInvalid', 'InvoiceExpired'], true)) {
			$this->invoiceFailed($data);

			return;
		}

		// Invoice has been confirmed
		if ('InvoiceSettled' === $eventType) {
			$this->invoiceSettled($data);

			return;
		}

		// Invoice was crated, but either order already exists or will be created on first payment, skip
		if ('InvoiceCreated' === $eventType) {
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
			$error = \sprintf('[WARNING] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);

			// Don't bother retrying
			return;
		}

		$this->processor->paymentReceived($bitcoinPayment);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \JsonException
	 */
	private function paymentReceivedDelayedOrder(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(\sprintf('[INFO] Payment received for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			$error = \sprintf('[WARNING] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);

			// Don't bother retrying
			return;
		}

		// Deal with the actual invoice now
		$this->processor->paymentReceivedCreateAfter($bitcoinPayment);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \JsonException
	 */
	private function paymentSettled(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(\sprintf('[INFO] One of the payments has settled for invoice %s', $invoiceId));

		if (null === ($bitcoinPayment = BitcoinPaymentRepository::getOneByInvoiceID($invoiceId))) {
			$error = \sprintf('[WARNING] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);

			// Don't bother retrying
			return;
		}

		// Deal with the actual invoice now
		$this->processor->paymentSettled($bitcoinPayment);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	private function invoiceFailed(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(\sprintf("[INFO] Invoice '%s' failed, either because it wasn't paid, it expired or it was marked as invalid", $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			$error = \sprintf('[WARNING] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);

			// Don't bother retrying
			return;
		}

		// If there is no order, don't bother updating it
		if (false === $bitcoinPayment->hasOrder()) {
			return;
		}

		// Check if protection is disabled, if so, just process the failure
		if (false === $this->configuration->get(Constants::CONFIGURATION_PROTECT_ORDERS, true)) {
			$this->processor->invoiceFailed($bitcoinPayment);
		}

		// Otherwise, will need to check the order so fetch it
		$order = new \Order($bitcoinPayment->getOrderId());

		// Check if the order has been paid, if so, add a note and abort
		if (\Validate::isLoadedObject($orderState = $order->getCurrentOrderState()) && $orderState->paid) {
			// Ensure we log this IPN
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog(\sprintf("[WARN] Webhook ('%s') received from BTCPay Server, but the order was already marked as paid.", $data['type']), \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, null, 'Order', $order->id);

			// Build a simple note and add it to the order
			$note = \sprintf("BTCPay Server: Webhook ('%s') received, but the order was already marked as paid.", $data['type']);
			CustomerMessage::addToOrder($this->context->shop, $order, $note);

			// Don't bother with the rest
			return;
		}

		// The order has not been set to paid, process the failure
		$this->processor->invoiceFailed($bitcoinPayment);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 */
	private function invoiceSettled(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(\sprintf("[INFO] Invoice '%s' has been settled and thus fully paid", $invoiceId));

		if (null === ($bitcoinPayment = $this->repository->getOneByInvoiceID($invoiceId))) {
			$error = \sprintf('[WARNING] Could not load order with invoice ID %s', $invoiceId);
			\PrestaShopLogger::addLog(\sprintf('[INFO] Received IPN: %s', \json_encode($data, \JSON_THROW_ON_ERROR)));
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING);

			// Don't bother retrying
			return;
		}

		// If there is no order, don't bother updating it
		if (false === $bitcoinPayment->hasOrder()) {
			return;
		}

		$this->processor->invoiceSettled($bitcoinPayment);
	}
}
