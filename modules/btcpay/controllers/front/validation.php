<?php

declare(strict_types=1);

use BTCPay\Invoice\PaymentReturn;

if (!defined('_PS_VERSION_')) {
	exit;
}

final class BTCPayValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * Enable SSL only.
	 */
	public bool $ssl = true;

	/** @var BTCPay */
	public $module;

	public function postProcess(): void
	{
		/** @var PaymentReturn $paymentReturn */
		$paymentReturn = $this->module->get(PaymentReturn::class);
		$result = $paymentReturn->handle();

		if (PaymentReturn::TEMPLATE === $result['kind']) {
			$this->setTemplate($result['target']);

			return;
		}

		if (PaymentReturn::NOTIFY === $result['kind']) {
			$this->warning[] = $result['warning'] ?? '';
			$this->redirectWithNotifications($result['target']);

			return;
		}

		Tools::redirect($result['target']);
	}
}
