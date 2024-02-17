<?php

namespace BTCPay\Repository;

use BTCPay\Factory\CustomerThread;

class CustomerThreadRepository
{
	/**
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 */
	public static function fetchOrCreateByByEmailAndOrder(\Shop $shop, \Order $order): \CustomerThread
	{
		// Get the customer
		$customer = $order->getCustomer();

		// Check if we need to create a thread, if so, create it and return it
		if (false === ($threadId = \CustomerThread::getIdCustomerThreadByEmailAndIdOrder($customer->email, $order->id))) {
			return CustomerThread::create($shop, $order);
		}

		// Return the existing thread
		return new \CustomerThread((int) $threadId);
	}
}
