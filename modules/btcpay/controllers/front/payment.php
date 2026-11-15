<?php

declare(strict_types=1);

use BTCPay\Constants;
use BTCPay\Server\Factory;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;

if (!defined('_PS_VERSION_')) {
	exit;
}

final class BTCPayPaymentModuleFrontController extends ModuleFrontController
{
	/**
	 * Enable SSL only.
	 */
	public bool $ssl = true;

	/** @var BTCPay */
	public $module;

	public function initContent(): void
	{
		parent::initContent();

		$configuration = $this->module->get(ShopConfigurationInterface::class);
		if (null === $configuration->get(Constants::CONFIGURATION_BTCPAY_STORE_ID)) {
			$this->warning[] = $this->context->getTranslator()->trans('Payment method has not yet been setup properly. Please try again or contact us.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));

			return;
		}

		try {
			/** @var Factory $factory */
			$factory = $this->module->get(Factory::class);
			if (null !== ($redirect = $factory->createPaymentRequest($this->context->customer, $this->context->cart))) {
				Tools::redirect($redirect);

				return;
			}

			$this->warning[] = $this->context->getTranslator()->trans('We could not create a payment request via BTCPay Server. Please try again or contact us.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
		} catch (Throwable $throwable) {
			PrestaShopLogger::addLog(\sprintf('[ERROR] An error occurred during payment creation: %s', $throwable), PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR, $throwable->getCode());

			$this->warning[] = $this->context->getTranslator()->trans('We are having issues with our BTCPay Server backend. Please try again or contact us.', [], 'Modules.Btcpay.Front');
			$this->redirectWithNotifications($this->context->link->getPageLink('cart', $this->ssl));
		}
	}
}
