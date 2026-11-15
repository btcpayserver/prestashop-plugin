<?php

declare(strict_types=1);

namespace BTCPay\Repository;

use BTCPay\Entity\BitcoinPayment;
use BTCPay\Invoice\CheckoutGuard;
use Doctrine\ORM\EntityManagerInterface;
use PrestaShopLogger;
use RuntimeException;
use Throwable;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class BitcoinPaymentRepository
{
	public function __construct(private readonly EntityManagerInterface $entityManager)
	{
	}

	public function create(int $cartId, string $status, string $invoiceId): BitcoinPayment
	{
		$bitcoinPayment = new BitcoinPayment();
		$bitcoinPayment->setCartId($cartId);
		$bitcoinPayment->setStatus($status);
		$bitcoinPayment->setInvoiceId($invoiceId);
		$this->save($bitcoinPayment);

		PrestaShopLogger::addLog('[INFO] Created bitcoin_payment for invoice ' . $invoiceId, PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE, null, 'BitcoinPayment', $bitcoinPayment->getId());

		return $bitcoinPayment;
	}

	public function findOneByInvoiceId(string $invoiceId): ?BitcoinPayment
	{
		return $this->findOneBy(['invoiceId' => $invoiceId]);
	}

	public function findOneByInvoiceReference(string $invoiceReference): ?BitcoinPayment
	{
		if (false === CheckoutGuard::isInvoiceReference($invoiceReference)) {
			return null;
		}

		return $this->findOneBy(['invoiceReference' => $invoiceReference]);
	}

	public function findOneByCartId(int $cartId): ?BitcoinPayment
	{
		return $this->findOneBy(['cartId' => $cartId]);
	}

	public function findOneByOrderId(int $orderId): ?BitcoinPayment
	{
		return $this->findOneBy(['orderId' => $orderId]);
	}

	public function save(BitcoinPayment $payment): void
	{
		try {
			$this->entityManager->persist($payment);
			$this->entityManager->flush();
		} catch (Throwable $throwable) {
			$error = \sprintf('[ERROR] Could not store bitcoin_payment: %s', $throwable->getMessage());
			PrestaShopLogger::addLog($error, PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, $throwable->getCode(), 'BitcoinPayment', $payment->getId());

			throw new RuntimeException($error, 0, $throwable);
		}
	}

	public function remove(BitcoinPayment $payment): void
	{
		try {
			$this->entityManager->remove($payment);
			$this->entityManager->flush();
		} catch (Throwable $throwable) {
			$error = \sprintf('[ERROR] Expected to remove the order %s, but failed to do so', $payment->getOrderId());
			PrestaShopLogger::addLog($error, PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, $throwable->getCode(), 'BitcoinPayment', $payment->getId());

			throw new RuntimeException($error, 0, $throwable);
		}
	}

	/**
	 * @param array<string, int|string> $criteria
	 */
	private function findOneBy(array $criteria): ?BitcoinPayment
	{
		$payment = $this->entityManager->getRepository(BitcoinPayment::class)->findOneBy($criteria);

		return $payment instanceof BitcoinPayment ? $payment : null;
	}
}
