<?php

namespace BTCPay\Repository;

use BTCPay\Entity\BitcoinPayment;
use BTCPay\Invoice\CheckoutGuard;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class BitcoinPaymentRepository
{
	/**
	 * @throws \PrestaShopException
	 */
	public static function create(int $cartId, string $status, string $invoiceId): BitcoinPayment
	{
		$bitcoinPayment = new BitcoinPayment();
		$bitcoinPayment->setCartId($cartId);
		$bitcoinPayment->setStatus($status);
		$bitcoinPayment->setInvoiceId($invoiceId);

		if (false === $bitcoinPayment->save(true)) {
			\PrestaShopLogger::addLog('[ERROR] Could not store bitcoin_payment', \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			throw new \RuntimeException('[ERROR] Could not store bitcoin_payment');
		}

		\PrestaShopLogger::addLog('[INFO] Created bitcoin_payment for invoice ' . $invoiceId, \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

		return $bitcoinPayment;
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	public static function getOneByInvoiceID(string $invoiceId): ?BitcoinPayment
	{
		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(\sprintf("bp.invoice_id = '%s'", \pSQL($invoiceId)))
			->limit(1);

		return self::hydrateOne($query);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	public static function getOneByInvoiceReference(string $invoiceReference): ?BitcoinPayment
	{
		if (false === CheckoutGuard::isInvoiceReference($invoiceReference)) {
			return null;
		}

		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(\sprintf("bp.invoice_reference = '%s'", \pSQL($invoiceReference)))
			->limit(1);

		return self::hydrateOne($query);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	public static function getOneByCartID(int $cartID): ?BitcoinPayment
	{
		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(\sprintf('bp.cart_id = %d', $cartID))
			->limit(1);

		return self::hydrateOne($query);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	public static function getOneByOrderID(int $orderID): ?BitcoinPayment
	{
		$query = new \DbQuery();
		$query->select('bp.*')
			->from('bitcoin_payment', 'bp')
			->where(\sprintf('bp.order_id = %d', $orderID))
			->limit(1);

		return self::hydrateOne($query);
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \JsonException
	 */
	private static function hydrateOne(\DbQuery $query): ?BitcoinPayment
	{
		$result = \Db::getInstance()->query($query);
		if (0 !== ($errorCode = (int) $result->errorCode())) {
			throw new \PrestaShopDatabaseException(\json_encode($result->errorInfo(), \JSON_THROW_ON_ERROR), $errorCode);
		}

		if (false === ($object = $result->fetchObject(BitcoinPayment::class))) {
			return null;
		}

		return $object;
	}
}
