<?php

use BTCPay\Server\Factory;

/** @noinspection AutoloadingIssuesInspection */
class BTCPayPaymentModuleFrontController extends ModuleFrontController
{
	/**
	 * Enable SSL only.
	 *
	 * @var bool
	 */
	public $ssl = true;

	/**
	 * @var Factory
	 */
	private $factory;

	public function __construct()
	{
		parent::__construct();

		$this->factory = new Factory($this->module->name);
	}

	/**
	 * {@inheritdoc}
	 */
	public function initContent(): void
	{
		parent::initContent();

		if (null !== ($redirect = $this->factory->createPaymentRequest($this->context->customer, $this->context->cart))) {
			Tools::redirectLink($redirect);

			return;
		}

		$this->warning[] = $this->context->getTranslator()->trans('We are having issues with our BTCPayServer backend. Please try again or contact us.', [], 'Modules.Btcpay.Front');
		$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
	}
}
