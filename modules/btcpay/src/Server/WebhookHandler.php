<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\Invoice\Processor;
use BTCPay\LegacyBitcoinPaymentRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Request;

class WebhookHandler
{
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

	public function __construct(\BTCPay $module, Client $client, LegacyBitcoinPaymentRepository $repository)
	{
		$this->repository    = $repository;
		$this->configuration = new Configuration();
		$this->processor     = new Processor($module, $this->configuration, $client);
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
		$dataType = (string) $data['type'];

		// Get order mode
		$orderMode = $this->configuration->get(Constants::CONFIGURATION_ORDER_MODE);

		// Payment has been received (order already exists)
		if (Constants::ORDER_MODE_BEFORE === $orderMode && \in_array($dataType, ['InvoiceProcessing', 'InvoicePaidInFull', 'InvoiceReceivedPayment'], true)) {
			$this->paymentReceived($data);

			return;
		}

		// Payment has been received (order needs to be made)
		if (Constants::ORDER_MODE_AFTER === $orderMode && \in_array($dataType, ['InvoiceProcessing', 'InvoicePaidInFull', 'InvoiceReceivedPayment'], true)) {
			$this->paymentReceivedDelayedOrder($data);

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
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	private function paymentFailed(array $data): void
	{
		$invoiceId = (string) $data['invoiceId'];
		\PrestaShopLogger::addLog(\sprintf('[INFO] Payment failed for invoice %s', $invoiceId));

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

		$this->processor->paymentFailed($bitcoinPayment);
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

		$this->processor->paymentConfirmed($bitcoinPayment);
	}
}
