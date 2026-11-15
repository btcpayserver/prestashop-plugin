<?php

declare(strict_types=1);

namespace BTCPay\Repository;

use Cache;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Throwable;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class OrderInvoicePaymentLinker
{
	public function __construct(private readonly Connection $connection)
	{
	}

	/**
	 * Mirrors \Order::setInvoice($use_existing_payment = true).
	 */
	public function linkUnlinkedPayments(int $orderId, int $orderInvoiceId): void
	{
		$prefix = \_DB_PREFIX_;
		$sql = 'SELECT DISTINCT op.id_order_payment
			FROM `' . $prefix . 'order_payment` op
			INNER JOIN `' . $prefix . 'orders` o ON (o.reference = op.order_reference)
			LEFT JOIN `' . $prefix . 'order_invoice_payment` oip ON (oip.id_order_payment = op.id_order_payment)
			WHERE (oip.id_order != :orderId OR oip.id_order IS NULL) AND o.id_order = :orderId';

		$idOrderPayments = $this->connection->fetchAllAssociative($sql, ['orderId' => $orderId]);
		if ([] === $idOrderPayments) {
			return;
		}

		foreach ($idOrderPayments as $orderPayment) {
			try {
				$this->connection->insert($prefix . 'order_invoice_payment', [
					'id_order_invoice' => $orderInvoiceId,
					'id_order_payment' => (int) $orderPayment['id_order_payment'],
					'id_order' => $orderId,
				]);
			} catch (Throwable $throwable) {
				throw new RuntimeException(\sprintf('Could not link order payment %d to invoice %d: %s', (int) $orderPayment['id_order_payment'], $orderInvoiceId, $throwable->getMessage()), 0, $throwable);
			}
		}

		Cache::clean('order_invoice_paid_*');
	}
}
