<?php

namespace BTCPay\Installer;

use BTCPay;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class Hooks
{
	/**
	 * @var BTCPay
	 */
	private $module;

	public function __construct(BTCPay $module)
	{
		$this->module = $module;
	}

	public function install(): array
	{
		if (!$this->module->registerHook('displayAdminOrderMainBottom')
			|| !$this->module->registerHook('displayOrderDetail')
			|| !$this->module->registerHook('paymentReturn')
			|| !$this->module->registerHook('paymentOptions')
			|| !$this->module->registerHook('actionCartSave')) {
			return [
				[
					'key'        => 'Could not register hooks',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				],
			];
		}

		return [];
	}
}
