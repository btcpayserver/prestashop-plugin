<?php

namespace BTCPay\Factory;

use BTCPay\Repository\CustomerThreadRepository;

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
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 */
	public static function addToOrder(\Shop $shop, \Order $order, string $message): void
	{
		// Find or create the customer thread
		$ct = CustomerThreadRepository::fetchOrCreate($shop, $order);

		// Ensure the thread is open
		$ct->status = 'open';
		$ct->update();

		// Create the message
		self::create($ct, $message);
	}
}
