<?php

namespace BTCPay\Server;

use BTCPay\Constants;
use BTCPay\LegacyOrderBitcoinRepository;
use Symfony\Component\HttpFoundation\Request;

class IPN
{
	/**
	 * @var LegacyOrderBitcoinRepository
	 */
	private $repository;

	public function __construct(LegacyOrderBitcoinRepository $repository)
	{
		$this->repository = $repository;
	}

	public function process(\BTCPay $btcpay, Request $request): void
	{
		$json = json_decode($request->getContent(), true);
		if (false === $json || null === $json) {
			return;
		}

		// Check if we received an event
		if (!\array_key_exists('event', $json)) {
			return;
		}

		// Get event from JSON
		$event = $json['event'];

		// Check if event code exist and is not empty
		if (!\array_key_exists('code', $event) || empty($event['code'])) {
			return;
		}

		// Check if event name exist and is not empty
		if (!\array_key_exists('name', $event) || empty($event['name'])) {
			return;
		}

		// Check if event data exist and is not empty
		if (!\array_key_exists('data', $json) || empty($json['data'])) {
			return;
		}

		// Get data
		$data = $json['data'];

		// Check if ID exists and that it's not empty.
		if (!\array_key_exists('id', $data) || empty($data['id'])) {
			return;
		}

		// Get order mode
		$orderMode = \Configuration::get('BTCPAY_ORDERMODE');

		// Invoice created - Before
		if ('invoice_created' === $event['name'] && Constants::ORDER_MODE_BEFORE === $orderMode) {
			$this->invoiceCreated($data, $btcpay);

			return;
		}

		// Invoice created - After
		if ('invoice_created' === $event['name'] && Constants::ORDER_MODE_AFTER === $orderMode) {
			\PrestaShopLogger::addLog('[INFO] Received invoice_created event, but not creating order because order mode is ' . Constants::ORDER_MODE_AFTER, 1);

			return;
		}

		// Payment Received - Before
		if ('invoice_receivedPayment' === $event['name'] && Constants::ORDER_MODE_BEFORE === $orderMode) {
			$this->receivedPaymentBefore($data);

			return;
		}

		// Payment Received - After
		if ('invoice_receivedPayment' === $event['name'] && Constants::ORDER_MODE_AFTER === $orderMode) {
			$this->receivedPaymentAfter($data, $btcpay);

			return;
		}

		// Pending full payment, not much to do but wait
		if ('invoice_paidInFull' === $event['name']) {
			return;
		}

		// Payment failed
		if ('invoice_failedToConfirm' === $event['name'] || 'invoice_markedInvalid' === $event['name'] || 'invoice_expired' === $event['name']) {
			$this->failedPayment($data);

			return;
		}

		// Payment confirmed
		if ('invoice_confirmed' === $event['name'] || 'invoice_markedComplete' === $event['name']) {
			$this->paymentConfirmed($data);

			return;
		}

		// Payment completed
		if ('invoice_completed' === $event['name']) {
			\PrestaShopLogger::addLog('[INFO] BTCPay server has told us that invoice "' . $data['id'] . '" is finished', 1);

			return;
		}

		// Log other IPN's.
		\PrestaShopLogger::addLog('[Error] Could not process IPN', 3);
		\PrestaShopLogger::addLog('[INFO] Received IPN: ' . $request->getContent(), 1);
	}

	private function invoiceCreated(array $data, \BTCPay $btcpay): void
	{
		$invoiceId = (string) $data['id'];
		\PrestaShopLogger::addLog('[Info] invoice created for ' . $invoiceId, 1);

		if (null === ($orderBitcoin = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[Error] Could not load order', 3);

			throw new \RuntimeException('[Error] Could not load order');
		}

		// waiting payment
		$orderStatus = \Configuration::get('BTCPAY_OS_WAITING');

		// on Order, just say payment processor is BTCPay
		$displayName = $btcpay->displayName;

		// fetch secure key, used to check cart comes from your prestashop
		$secure_key = $data['posData'];
		if (false === isset($secure_key)) {
			\PrestaShopLogger::addLog('[Error] No securekey', 3);

			throw new \RuntimeException('[Error] No securekey');
		}

		// rate in fiat currency
		$rate = $data['rate'];
		if (false === isset($rate)) {
			\PrestaShopLogger::addLog('[Error] No rate', 3);

			throw new \RuntimeException('[Error] No rate');
		}

		// generate an order only if their is not another one with this cart
		$orderId = \Order::getIdByCartId($orderBitcoin->getCartId());
		if (false === $orderId || 0 === $orderId) {
			$btcpay->validateOrder(
				$orderBitcoin->getCartId(),
				$orderStatus,
				$orderBitcoin->getAmount(),
				$displayName, //bitcoin btcpay
				null, //message should be new Message
				[], //extravars for mail
				null, //currency special
				false, // don't touch amount
				$secure_key
			);

			// Get the new order ID
			$orderId = (int) \Order::getIdByCartId($orderBitcoin->getCartId());

			$orderBitcoin->setOrderId($orderId);
			$orderBitcoin->setBitcoinPaid('0.0');
			$orderBitcoin->setStatus((string) $orderStatus);

			// Update the object
			if (false === $orderBitcoin->update(true)) {
				$error = '[Error] Could not update bitcoin_payment: ' . \Db::getInstance()->getMsgError();
				\PrestaShopLogger::addLog($error, 3);
				throw new \RuntimeException($error);
			}

			return;
		}

		// Order already paid
		\PrestaShopLogger::addLog('[Error] already created order', 1);

		throw new \RuntimeException('[Error] already created order');
	}

	private function receivedPaymentBefore(array $data): void
	{
		$invoiceId = (string) $data['id'];
		\PrestaShopLogger::addLog('[Info] payment received for ' . $invoiceId, 1);

		if (null === ($orderBitcoin = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[Error] Could not load order', 3);

			throw new \RuntimeException('[Error] Could not load order');
		}

		$orderId = $orderBitcoin->getOrderId();

		// waiting confirmation
		$orderStatus = \Configuration::get('BTCPAY_OS_CONFIRMING');

		$orderBitcoin->setBitcoinPaid($data['btcPaid']);
		$orderBitcoin->setStatus((string) $orderStatus);

		// Update the object
		if (false === $orderBitcoin->update(true)) {
			$error = '[Error] Could not update bitcoin_payment: ' . \Db::getInstance()->getMsgError();
			\PrestaShopLogger::addLog($error, 3);
			throw new \RuntimeException($error);
		}

		// add Order status change to Order history table
		$orderHistory           = new \OrderHistory();
		$orderHistory->id_order = $orderId;
		// bitcoin confirmation ok
		$orderHistory->changeIdOrderState($orderStatus, $orderId, true);
		//add with email is mandatory to add new order state in order_history
		$orderHistory->add(true);
	}

	private function receivedPaymentAfter(array $data, \BTCPay $btcpay): void
	{
		$invoiceId = (string) $data['id'];
		\PrestaShopLogger::addLog('[Info] payment received for ' . $invoiceId, 1);

		if (null === ($orderBitcoin = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[Error] Could not load order', 3);

			throw new \RuntimeException('[Error] Could not load order');
		}

		// waiting confirmation
		$orderStatus = \Configuration::get('BTCPAY_OS_CONFIRMING');

		// on Order, just say payment processor is BTCPay
		$displayName = $btcpay->displayName;

		// fetch secure key, used to check cart comes from your prestashop
		$secure_key = $data['posData'];
		if (false === isset($secure_key)) {
			\PrestaShopLogger::addLog('[Error] No securekey', 3);

			throw new \RuntimeException('[Error] No securekey');
		}

		// rate in fiat currency
		$rate = $data['rate'];
		if (false === isset($rate)) {
			\PrestaShopLogger::addLog('[Error] No rate', 3);

			throw new \RuntimeException('[Error] No rate');
		}

		// generate an order only if their is not another one with this cart
		$orderId = \Order::getIdByCartId($orderBitcoin->getCartId());
		if (false === $orderId || 0 === $orderId) {
			$btcpay->validateOrder(
				$orderBitcoin->getCartId(),
				$orderStatus,
				$orderBitcoin->getAmount(),
				$displayName, //bitcoin btcpay
				$rate, //message
				[], //extravars
				null, //currency special
				false, // don't touch amount
				$secure_key
			);

			// Get the new order ID
			$orderId = (int) \Order::getIdByCartId($orderBitcoin->getCartId());

			$orderBitcoin->setOrderId($orderId);
			$orderBitcoin->setBitcoinPaid($data['btcPaid']);
			$orderBitcoin->setStatus((string) $orderStatus);

			// Update the object
			if (false === $orderBitcoin->update(true)) {
				$error = '[Error] Could not update bitcoin_payment: ' . \Db::getInstance()->getMsgError();
				\PrestaShopLogger::addLog($error, 3);
				throw new \RuntimeException($error);
			}

			return;
		}

		// Order already paid
		\PrestaShopLogger::addLog('[Error] already paid order', 1);

		throw new \RuntimeException('[Error] already paid order');
	}

	private function failedPayment(array $data): void
	{
		$invoiceId = (string) $data['id'];
		\PrestaShopLogger::addLog('[Info] payment failed for ' . $invoiceId, 1);

		if (null === ($orderBitcoin = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[Error] Could not load order', 3);

			throw new \RuntimeException('[Error] Could not load order');
		}

		// wait for confirm
		$orderStatus = \Configuration::get('BTCPAY_OS_CONFIRMING');

		if ('invalid' === $data['status'] || 'expired' === $data['status']) {
			// time setup on invoice is expired
			$orderStatus = \Configuration::get('BTCPAY_OS_FAILED');
		}

		$orderBitcoin->setStatus((string) $orderStatus);

		// Update the object
		if (false === $orderBitcoin->update(true)) {
			$error = '[Error] Could not update bitcoin_payment: ' . \Db::getInstance()->getMsgError();
			\PrestaShopLogger::addLog($error, 3);
			throw new \RuntimeException($error);
		}

		// add Order status change to Order history table
		$orderHistory           = new \OrderHistory();
		$orderHistory->id_order = $orderBitcoin->getOrderId();

		// bitcoin confirmation ok
		$orderHistory->changeIdOrderState($orderStatus, $orderBitcoin->getOrderId(), true);
		$orderHistory->add(true);
	}

	private function paymentConfirmed(array $data): void
	{
		$invoiceId = (string) $data['id'];
		\PrestaShopLogger::addLog('[Info] payment confirmed for ' . $invoiceId, 1);

		if (null === ($orderBitcoin = $this->repository->getOneByInvoiceID($invoiceId))) {
			\PrestaShopLogger::addLog('[Error] Could not load order', 3);

			throw new \RuntimeException('[Error] Could not load order');
		}

		$order = new \Order($orderBitcoin->getOrderId());

		// wait for confirm
		$orderStatus = \Configuration::get('BTCPAY_OS_CONFIRMING');

		if ('invalid' === $data['status'] || 'expired' === $data['status']) {
			// time setup on invoice is expired
			$orderStatus = \Configuration::get('BTCPAY_OS_FAILED');
		}

		if ('paid' === $data['status']) {
			// TX received but we have to wait some confirmation
			$orderStatus = \Configuration::get('BTCPAY_OS_CONFIRMING');
		}

		if ('confirmed' === $data['status'] || 'complete' === $data['status']) {
			//Transaction confirmed
			$orderStatus = \Configuration::get('BTCPAY_OS_PAID');
		}

		$orderBitcoin->setStatus((string) $orderStatus);

		// Update the object
		if (false === $orderBitcoin->update(true)) {
			$error = '[Error] Could not update bitcoin_payment: ' . \Db::getInstance()->getMsgError();
			\PrestaShopLogger::addLog($error, 3);
			throw new \RuntimeException($error);
		}

		// add Order status change to Order history table
		if ($order->current_state !== $orderStatus) {
			$orderHistory           = new \OrderHistory();
			$orderHistory->id_order = $orderBitcoin->getOrderId();
			// bitcoin confirmation ok
			$orderHistory->changeIdOrderState($orderStatus, $orderBitcoin->getOrderId(), true);
			$orderHistory->add(true);
		} else {
			\PrestaShopLogger::addLog('[Info] current state is not different than new order status in invoice confirmed', 1);
		}
	}
}
