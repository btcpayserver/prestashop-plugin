<?php

namespace BTCPay\Server;

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
			\PrestaShopLogger::addLog(sprintf('[Error] JSON encoding issue: %s', json_last_error_msg()), 1);

			throw new \RuntimeException(sprintf('[Error] JSON encoding issue: %s', json_last_error_msg()));
		}

		if (!\array_key_exists('event', $json)) {
			return;
		}

		$event = $json['event'];

		$data = [];
		if (true === \array_key_exists('data', $json)) {
			$data = $json['data'];
		}

		if (empty($event['code'])) {
			\PrestaShopLogger::addLog('[Error] Event code missing from callback.', 1);

			throw new \RuntimeException('[Error] Event code missing from callback.');
		}

		if (true === empty($data)) {
			\PrestaShopLogger::addLog('[Error] invalide json', 3);

			throw new \RuntimeException('[Error] invalide json');
		}

		$orderMode = \Configuration::get('BTCPAY_ORDERMODE');

		// Invoice created
		if (\array_key_exists('name', $event) && 'invoice_created' === $event['name'] && 'beforepayment' === $orderMode) {
			$this->invoiceCreated($data, $btcpay);

			return;
		}

		// Payment Received
		if (\array_key_exists('name', $event) && 'invoice_receivedPayment' === $event['name'] && 'afterpayment' === $orderMode) {
			$this->receivedPaymentAfter($data, $btcpay);

			return;
		}

		// Payment Received
		if (\array_key_exists('name', $event) && 'invoice_receivedPayment' === $event['name'] && 'beforepayment' === $orderMode) {
			$this->receivedPaymentBefore($data);

			return;
		}

		// Pending full payment, not much to do but wait
		if (\array_key_exists('name', $event) && 'invoice_paidInFull' === $event['name']) {
			return;
		}

		// Payment failed
		if (\array_key_exists('name', $event) && ('invoice_failedToConfirm' === $event['name'] || 'invoice_markedInvalid' === $event['name'] || 'invoice_expired' === $event['name'])) {
			$this->failedPayment($data);

			return;
		}

		// Payment Confirmed
		if (true === \array_key_exists('name', $event) && ('invoice_confirmed' === $event['name'] || 'invoice_markedComplete' === $event['name'])) {
			$this->paymentConfirmed($data);

			return;
		}

		// Payment completed
		if (true === \array_key_exists('name', $event) && 'invoice_completed' === $event['name']) {
			\PrestaShopLogger::addLog('[INFO] BTCPay server has told us that invoice "' . $data['id'] . '" is finished', 1);

			return;
		}

		\PrestaShopLogger::addLog('[Error] Could not process IPN', 3);
		\PrestaShopLogger::addLog('[INFO] Received IPN: ' . $json, 1);
	}

	private function invoiceCreated(array $data, \BTCPay $btcpay): void
	{
		// sleep to not receive ipn notification
		// before the update of bitcoin order table
		// sleep(15);

		if (false === \array_key_exists('id', $data)) {
			\PrestaShopLogger::addLog('[Error] No data id', 3);

			throw new \RuntimeException('[Error] No data id');
		}

		if (false === \array_key_exists('url', $data)) {
			\PrestaShopLogger::addLog('[Error] No data url', 3);

			throw new \RuntimeException('[Error] No data url');
		}

		// get invoice id, to go back on cart and check the amount
		$invoiceId = (string) $data['id'];
		if (false === isset($invoiceId)) {
			\PrestaShopLogger::addLog('[Error] No invoice id', 3);

			throw new \RuntimeException('[Error] No invoice id');
		}

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

			// add Order status change to Order history table
			$orderHistory           = new \OrderHistory();
			$orderHistory->id_order = $orderId;
			// bitcoin confirmation ok
			$orderHistory->changeIdOrderState($orderStatus, $orderId, true);
			//add with email is mandatory to add new order state in order_history
			$orderHistory->add(true);

			return;
		}

		// Order already paid
		\PrestaShopLogger::addLog('[Error] already created order', 1);

		throw new \RuntimeException('[Error] already created order');
	}

	private function receivedPaymentBefore(array $data): void
	{
		\PrestaShopLogger::addLog('[Info] payment received', 1);

		if (false === \array_key_exists('id', $data)) {
			\PrestaShopLogger::addLog('[Error] No id in data', 3);

			throw new \RuntimeException('[Error] No id in data');
		}

		// get invoice id, to go back on cart and check the amount
		$invoiceId = (string) $data['id'];
		if (false === isset($invoiceId)) {
			\PrestaShopLogger::addLog('[Error] No invoice id', 3);

			throw new \RuntimeException('[Error] No invoice id');
		}

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
		// sleep to not receive ipn notification
		// before the update of bitcoin order table
		// sleep(15);

		if (false === \array_key_exists('id', $data)) {
			\PrestaShopLogger::addLog('[Error] No data id', 3);

			throw new \RuntimeException('[Error] No data id');
		}

		if (false === \array_key_exists('url', $data)) {
			\PrestaShopLogger::addLog('[Error] No data url', 3);

			throw new \RuntimeException('[Error] No data url');
		}

		// get invoice id, to go back on cart and check the amount
		$invoiceId = (string) $data['id'];
		if (false === isset($invoiceId)) {
			\PrestaShopLogger::addLog('[Error] No invoice id', 3);

			throw new \RuntimeException('[Error] No invoice id');
		}

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

			// add Order status change to Order history table
			$orderHistory           = new \OrderHistory();
			$orderHistory->id_order = $orderId;
			// bitcoin confirmation ok
			$orderHistory->changeIdOrderState($orderStatus, $orderId, true);
			$orderHistory->add(true);

			return;
		}

		// Order already paid
		\PrestaShopLogger::addLog('[Error] already paid order', 1);

		throw new \RuntimeException('[Error] already paid order');
	}

	private function failedPayment(array $data): void
	{
		if (false === \array_key_exists('id', $data)) {
			\PrestaShopLogger::addLog('[Error] No id in data', 3);

			throw new \RuntimeException('[Error] No id in data');
		}

		if (false === \array_key_exists('url', $data)) {
			\PrestaShopLogger::addLog('[Error] No url in data', 3);

			throw new \RuntimeException('[Error] No url in data');
		}

		// get invoice id, to go back on cart and check the amount
		$invoiceId = (string) $data['id'];
		if (false === isset($invoiceId)) {
			\PrestaShopLogger::addLog('[Error] No invoice id', 3);

			throw new \RuntimeException('[Error] No invoice id');
		}

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
		if (false === \array_key_exists('id', $data)) {
			\PrestaShopLogger::addLog('[Error] No id in data', 3);

			throw new \RuntimeException('[Error] No id in data');
		}

		if (false === \array_key_exists('url', $data)) {
			\PrestaShopLogger::addLog('[Error] No url in data', 3);

			throw new \RuntimeException('[Error] No url in data');
		}

		// get invoice id, to go back on cart and check the amount
		$invoiceId = (string) $data['id'];
		if (false === isset($invoiceId)) {
			\PrestaShopLogger::addLog('[Error] No invoice id', 3);

			throw new \RuntimeException('[Error] No invoice id');
		}

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
