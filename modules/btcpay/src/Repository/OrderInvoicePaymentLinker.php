<?php

namespace BTCPay\Repository;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class OrderInvoicePaymentLinker
{
	/**
	 * Mirrors \Order::setInvoice($use_existing_payment = true).
	 *
	 * @throws \PrestaShopDatabaseException
	 */
	public static function linkUnlinkedPayments(int $orderId, int $orderInvoiceId): void
	{
		$idOrderPayments = \Db::getInstance()->executeS(
			'SELECT DISTINCT op.id_order_payment
			FROM `' . \_DB_PREFIX_ . 'order_payment` op
			INNER JOIN `' . \_DB_PREFIX_ . 'orders` o ON (o.reference = op.order_reference)
			LEFT JOIN `' . \_DB_PREFIX_ . 'order_invoice_payment` oip ON (oip.id_order_payment = op.id_order_payment)
			WHERE (oip.id_order != ' . $orderId . ' OR oip.id_order IS NULL) AND o.id_order = ' . $orderId
		);

		if (empty($idOrderPayments)) {
			return;
		}

		foreach ($idOrderPayments as $orderPayment) {
			if (false === \Db::getInstance()->insert('order_invoice_payment', [
				'id_order_invoice' => $orderInvoiceId,
				'id_order_payment' => (int) $orderPayment['id_order_payment'],
				'id_order'         => $orderId,
			])) {
				throw new \RuntimeException(\sprintf('Could not link order payment %d to invoice %d: %s', (int) $orderPayment['id_order_payment'], $orderInvoiceId, \Db::getInstance()->getMsgError()));
			}
		}

		\Cache::clean('order_invoice_paid_*');
	}
}
