<?php

namespace BTCPay\Invoice;

use BTCPay\Constants;
use BTCPay\Entity\BitcoinPayment;
use BTCPay\Factory\CustomerMessage;
use BTCPay\Repository\OrderPaymentRepository;
use BTCPay\Server\Client;
use BTCPayServer\Result\Invoice;
use PrestaShop\PrestaShop\Adapter\Configuration;

if (!\defined('_PS_VERSION_')) {
	exit;
}

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
		$this->updateOrderStatus($bitcoinPayment, $orderStatus, $invoice);
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
		$this->updateOrderStatus($bitcoinPayment, $orderStatus, $invoice);
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
		$orderStatus = $this->statusFromInvoice($invoice, (string) $order->current_state);

		// If nothing changed, return
		if ((string) $order->current_state === $orderStatus) {
			\PrestaShopLogger::addLog(\sprintf("[INFO] The state is the same as the one received for invoice '%s'", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'Order', $order->id);

			return;
		}

		// Store the updated status
		$this->updateOrderStatus($bitcoinPayment, $orderStatus, $invoice);
	}

	/**
	 * @throws \JsonException
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public function paymentReceivedCreateAfter(BitcoinPayment $bitcoinPayment): void
	{
		// Generate an order only if there is not another one with this cart
		if (null !== ($existing = \Order::getByCartId($bitcoinPayment->getCartId()))) {
			if (false === $bitcoinPayment->hasOrder()) {
				$bitcoinPayment->setOrderId((int) $existing->id);
				$bitcoinPayment->update(true);
			}

			// We already have an order, so process as normal
			$this->paymentReceived($bitcoinPayment);

			return;
		}

		// Get the store ID
		$storeID = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID);

		// Grab the invoice from the server
		$invoice = $this->client->invoice()->getInvoice($storeID, $bitcoinPayment->getInvoiceId());

		// Only create an order once BTCPay has actually seen a payment
		if (false === CheckoutGuard::canCreateOrder(
			$invoice->isPartiallyPaid(),
			$invoice->isProcessing(),
			$invoice->isPaidLate(),
			$invoice->isOverpaid(),
			$invoice->isSettled()
		)) {
			\PrestaShopLogger::addLog(\sprintf("[INFO] Invoice '%s' has not received a payment yet, skipping order creation", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

			return;
		}

		if (false === $this->hydrateSnapshotFromInvoice($bitcoinPayment, $invoice)) {
			return;
		}

		// Compare the BTCPay invoice, stored amount, and current cart before creating the order
		$cart = new \Cart($bitcoinPayment->getCartId());
		if (false === $this->checkoutValueHolds($bitcoinPayment, $invoice, $cart, null)) {
			\PrestaShopLogger::addLog(\sprintf("[ERROR] Invoice '%s' amount/currency does not match the stored snapshot or current cart", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			return;
		}

		// Fetch the secure key, which is used to check if the order has been made from this store
		if (null === ($invoiceData = $invoice->getData()) || !\array_key_exists('metadata', $invoiceData) || !\array_key_exists('posData', $invoiceData['metadata'])) {
			\PrestaShopLogger::addLog('[ERROR] Secure key was not defined', \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			return;
		}

		// Set an initial state
		$orderStatus = $this->statusFromInvoice($invoice, (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING));

		// Grab the secure key
		$secureKey = $invoiceData['metadata']['posData'];

		if ($invoice->isPaidLate()) {
			// Add a regular log
			\PrestaShopLogger::addLog(\sprintf("[INFO] User paid after expiration for this invoice '%s'", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE);
		}

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
		if (null === $order || 0 === (int) $order->id) {
			\PrestaShopLogger::addLog(\sprintf("[ERROR] Order was not created for invoice '%s'", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $bitcoinPayment->getId());

			return;
		}

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
	private function updateOrderStatus(BitcoinPayment $bitcoinPayment, string $orderStatus, Invoice $invoice): void
	{
		$order = new \Order($bitcoinPayment->getOrderId());

		$currentPaid = \Validate::isLoadedObject($currentState = $order->getCurrentOrderState()) && (bool) $currentState->paid;
		$targetPaid  = $this->orderStateIsPaid($orderStatus);

		// Do not downgrade orders that are already paid when protection is enabled
		if (false === CheckoutGuard::paidTransitionAllowed($this->protectsOrders(), $currentPaid, $targetPaid)) {
			\PrestaShopLogger::addLog(\sprintf("[WARN] Refusing to move paid order to state '%s'", $orderStatus), \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING, null, 'Order', $order->id);
			CustomerMessage::addToOrder($this->context->shop, $order, \sprintf("BTCPay Server: ignored a status change to '%s' because the order is already paid.", $orderStatus));

			return;
		}

		// Verify the invoice still matches the checkout snapshot before marking paid
		if ($targetPaid && false === $this->checkoutValueHolds($bitcoinPayment, $invoice, null, $order)) {
			\PrestaShopLogger::addLog(\sprintf("[ERROR] Refusing to mark order paid; invoice '%s' amount/currency does not match the checkout snapshot", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'Order', $order->id);

			return;
		}

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

	private function statusFromInvoice(Invoice $invoice, string $default): string
	{
		// Map the authoritative BTCPay invoice state to a PrestaShop order state
		$orderStatus = $default;

		if ($invoice->isPartiallyPaid()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING);
		}

		if ($invoice->isProcessing()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		if ($invoice->isPaidLate()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		if ($invoice->isOverpaid()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
		}

		if ($invoice->isSettled()) {
			$orderStatus = (string) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		}

		return $orderStatus;
	}

	private function orderStateIsPaid(string $orderStatus): bool
	{
		$state = new \OrderState((int) $orderStatus);

		return \Validate::isLoadedObject($state) && (bool) $state->paid;
	}

	private function protectsOrders(): bool
	{
		return (bool) $this->configuration->get(Constants::CONFIGURATION_PROTECT_ORDERS, true);
	}

	private function invoiceAmount(Invoice $invoice): ?string
	{
		$data = $invoice->getData();
		if (!\is_array($data) || !isset($data['amount']) || !\is_string($data['amount']) || '' === $data['amount']) {
			return null;
		}

		return $data['amount'];
	}

	private function invoiceCurrency(Invoice $invoice): ?string
	{
		$data = $invoice->getData();
		if (!\is_array($data) || !isset($data['currency']) || !\is_string($data['currency']) || '' === $data['currency']) {
			return null;
		}

		return \strtoupper($data['currency']);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	private function hydrateSnapshotFromInvoice(BitcoinPayment $payment, Invoice $invoice): bool
	{
		$amount = $this->invoiceAmount($invoice);
		if (null === $amount) {
			\PrestaShopLogger::addLog(\sprintf("[ERROR] Invoice '%s' is missing amount", $invoice->getId()), \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $payment->getId());

			return false;
		}

		// Backfill amount for rows created before amount was persisted on the ObjectModel
		if (null === $payment->getAmount() || '' === $payment->getAmount()) {
			$payment->setAmount($amount);
			if (false === $payment->update(true)) {
				$error = \sprintf('[ERROR] Could not persist checkout snapshot: %s', \Db::getInstance()->getMsgError());
				\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, null, 'BitcoinPayment', $payment->getId());

				return false;
			}
		}

		return true;
	}

	private function checkoutValueHolds(BitcoinPayment $payment, Invoice $invoice, ?\Cart $cart, ?\Order $order): bool
	{
		$invoiceAmount   = $this->invoiceAmount($invoice);
		$invoiceCurrency = $this->invoiceCurrency($invoice);
		$storedAmount    = $payment->getAmount();

		if (null === $invoiceAmount || null === $invoiceCurrency || null === $storedAmount || '' === $storedAmount) {
			return false;
		}

		if (false === CheckoutGuard::amountsMatch($invoiceAmount, $storedAmount)) {
			return false;
		}

		if (null !== $cart) {
			$cartAmount   = (string) $cart->getOrderTotal(true);
			$cartCurrency = \strtoupper((string) \Currency::getCurrencyInstance($cart->id_currency)->iso_code);
			if (false === CheckoutGuard::amountsMatch($storedAmount, $cartAmount) || $cartCurrency !== $invoiceCurrency) {
				return false;
			}
		}

		if (null !== $order) {
			$orderAmount   = (string) $order->total_paid_tax_incl;
			$orderCurrency = \strtoupper((string) \Currency::getCurrencyInstance($order->id_currency)->iso_code);
			if (false === CheckoutGuard::amountsMatch($storedAmount, $orderAmount) || $orderCurrency !== $invoiceCurrency) {
				return false;
			}
		}

		return true;
	}
}
