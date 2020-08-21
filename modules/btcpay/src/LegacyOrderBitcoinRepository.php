<?php

namespace BTCPay;

use BTCPay\Entity\BitcoinPayment;

class LegacyOrderBitcoinRepository
{
	/**
	 * @var \Db
	 */
	private $connection;

	public function __construct()
	{
		$this->connection = \Db::getInstance();
	}

	public function create(int $cartId, string $status, string $invoiceId): BitcoinPayment
	{
		$orderBitcoin = new BitcoinPayment();
		$orderBitcoin->setCartId($cartId);
		$orderBitcoin->setStatus($status);
		$orderBitcoin->setInvoiceId($invoiceId);

		if (false === $orderBitcoin->save(true)) {
			\PrestaShopLogger::addLog('[ERROR] Could not store bitcoin_payment', 3);

			throw new \RuntimeException('[ERROR] Could not store bitcoin_payment');
		}

		\PrestaShopLogger::addLog('Created bitcoin_payment for invoice ' . $invoiceId);

		return $orderBitcoin;
	}

	public function getOneByInvoiceID(string $invoiceId): ?BitcoinPayment
	{
		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(sprintf('bp.invoice_id = "%s"', $invoiceId))
			->limit(1);

		$result = $this->connection->query($query);
		if (0 !== ($errorCode = (int) $result->errorCode())) {
			throw new \PrestaShopDatabaseException(json_encode($result->errorInfo()), $errorCode);
		}

		if (false === ($object = $result->fetchObject(BitcoinPayment::class))) {
			return null;
		}

		return $object;
	}

	public function getOneByInvoiceReference(string $invoiceReference): ?BitcoinPayment
	{
		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(sprintf('bp.invoice_reference = "%s"', $invoiceReference))
			->limit(1);

		$result = $this->connection->query($query);
		if (0 !== ($errorCode = (int) $result->errorCode())) {
			throw new \PrestaShopDatabaseException(json_encode($result->errorInfo()), $errorCode);
		}

		if (false === ($object = $result->fetchObject(BitcoinPayment::class))) {
			return null;
		}

		return $object;
	}

	public function getOneByCartID(int $cartID): ?BitcoinPayment
	{
		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(sprintf('bp.cart_id = "%s"', $cartID))
			->limit(1);

		$result = $this->connection->query($query);
		if (0 !== ($errorCode = (int) $result->errorCode())) {
			throw new \PrestaShopDatabaseException(json_encode($result->errorInfo()), $errorCode);
		}

		if (false === ($object = $result->fetchObject(BitcoinPayment::class))) {
			return null;
		}

		return $object;
	}

	public function getOneByOrderID(int $orderID): ?BitcoinPayment
	{
		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(sprintf('bp.id_order = "%s"', $orderID))
			->limit(1);

		$result = $this->connection->query($query);
		if (0 !== ($errorCode = (int) $result->errorCode())) {
			throw new \PrestaShopDatabaseException(json_encode($result->errorInfo()), $errorCode);
		}

		if (false === ($object = \Db::getInstance()->query($query)->fetchObject(BitcoinPayment::class))) {
			return null;
		}

		return $object;
	}
}
