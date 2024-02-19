<?php

namespace BTCPay\Factory;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class CustomerThread
{
	/**
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 */
	public static function create(\Shop $shop, \Order $order): \CustomerThread
	{
		// Grab the customer from the order
		$customer = $order->getCustomer();

		// Create a customer thread
		$ct              = new \CustomerThread();
		$ct->id_contact  = 0;
		$ct->id_customer = $order->id_customer;
		$ct->id_shop     = (int) $shop->id;
		$ct->id_order    = (int) $order->id;
		$ct->id_lang     = \Language::getIdByIso('en');
		$ct->email       = $customer->email;
		$ct->status      = 'open';
		$ct->token       = \Tools::passwdGen(12);

		// Ensure it is actually created
		$ct->add();

		return $ct;
	}
}
