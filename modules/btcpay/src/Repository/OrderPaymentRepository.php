<?php

namespace BTCPay\Repository;

use BTCPayServer\Result\InvoicePayment;
use PrestaShopCollection;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class OrderPaymentRepository
{
	/**
	 * @throws \PrestaShopException
	 */
	public static function hasPayment(\Order $order, InvoicePayment $payment): bool
	{
		$order_payments = new PrestaShopCollection(\OrderPayment::class);
		$order_payments->where('order_reference', '=', $order->reference);
		$order_payments->where('transaction_id', '=', $payment->getTransactionId());

		return false === empty($order_payments->getResults());
	}
}
