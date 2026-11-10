<?php

namespace BTCPay\Repository;

use PrestaShopCollection;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class OrderPaymentRepository
{
	/**
	 * @return array<string, string> transaction_id => amount
	 *
	 * @throws \PrestaShopException
	 */
	public static function amountsByTransactionId(\Order $order): array
	{
		$order_payments = new PrestaShopCollection(\OrderPayment::class);
		$order_payments->where('order_reference', '=', $order->reference);

		$map = [];
		foreach ($order_payments->getResults() as $orderPayment) {
			$transactionId = (string) $orderPayment->transaction_id;
			if ('' === $transactionId) {
				continue;
			}

			$map[$transactionId] = (string) $orderPayment->amount;
		}

		return $map;
	}
}
