<?php

use BTCPay\Constants;
use BTCPay\Server\Factory;

class BTCPayPaymentModuleFrontController extends ModuleFrontController
{
	/**
	 * Enable SSL only.
	 *
	 * @var bool
	 */
	public $ssl = true;

	/**
	 * @var BTCPay
	 */
	public $module;

	/**
	 * @var Factory
	 */
	private $factory;

	/**
	 * @var Configuration
	 */
	private $configuration;

	public function __construct()
	{
		parent::__construct();

		$this->factory       = new Factory($this->module, $this->context);
		$this->configuration = new Configuration();
	}

	public function initContent(): void
	{
		parent::initContent();

		if (null === $this->configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID)) {
			$this->warning[] = $this->context->getTranslator()->trans('Payment method has not yet been setup properly. Please try again or contact us.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));

			return;
		}

		try {
			if (null !== ($redirect = $this->factory->createPaymentRequest($this->context->customer, $this->context->cart))) {
				Tools::redirectLink($redirect);

				return;
			}

			$this->warning[] = $this->context->getTranslator()->trans('We could not create a payment request via BTCPay Server. Please try again or contact us.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
		} catch (\Throwable $e) {
			$this->warning[] = $this->context->getTranslator()->trans('We are having issues with our BTCPay Server backend. Please try again or contact us.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
		}
	}
}
