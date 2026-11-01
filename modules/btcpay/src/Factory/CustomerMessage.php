<?php

namespace BTCPay\Factory;

use BTCPay\Repository\CustomerThreadRepository;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class CustomerMessage
{
	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public static function create(\CustomerThread $ct, string $message): \CustomerMessage
	{
		// Create a customer message
		$cm                     = new \CustomerMessage();
		$cm->id_customer_thread = $ct->id;
		$cm->id_employee        = 0;
		$cm->message            = $message;
		$cm->private            = true;
		$cm->read               = true;

		// Ensure it is actually created
		$cm->add();

		return $cm;
	}

	/**
	 * Adds a private note to the order thread. The note is informational only: a failure is logged and never
	 * interrupts the caller (the order status has already been changed when this is called from the webhook).
	 */
	public static function addToOrder(\Shop $shop, \Order $order, string $message): void
	{
		try {
			// Find or create the customer thread
			$ct = CustomerThreadRepository::fetchOrCreate($shop, $order);

			// Ensure the thread is open
			$ct->status = 'open';
			$ct->update();

			// Create the message
			self::create($ct, $message);
		} catch (\Throwable $throwable) {
			\PrestaShopLogger::addLog(
				\sprintf('[WARNING] Could not add the BTCPay note to order %d: %s', (int) $order->id, $throwable->getMessage()),
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING,
				null,
				'Order',
				(int) $order->id
			);
		}
	}
}
