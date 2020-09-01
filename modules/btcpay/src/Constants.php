<?php

namespace BTCPay;

use BTCPayServer\Invoice;

class Constants
{
	/**
	 * All possible transaction speeds as defined by BTCPay server
	 */
	public const TRANSACTION_SPEEDS = [Invoice::TRANSACTION_SPEED_LOW, Invoice::TRANSACTION_SPEED_MEDIUM, Invoice::TRANSACTION_SPEED_HIGH];

	/**
	 * All possible options for order creation (before or after payment)
	 */
	public const ORDER_MODES       = [self::ORDER_MODE_BEFORE, self::ORDER_MODE_AFTER];
	public const ORDER_MODE_BEFORE = 'beforepayment';
	public const ORDER_MODE_AFTER  = 'afterpayment';
}
