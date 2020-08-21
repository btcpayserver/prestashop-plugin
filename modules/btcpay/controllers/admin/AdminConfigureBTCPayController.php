<?php
class AdminConfigureBTCPayController extends ModuleAdminController
{
	/**
	 * BTCpay module instance. It's assigned automatically by PrestaShop  infrastructure.
	 *
	 * @var BTCPay
	 */
	public $module;

	/**
	 * Configure the administration controller and define some sane defaults.
	 *
	 * {@inheritdoc}
	 */
	public function __construct()
	{
		$this->bootstrap = true;
		$this->display   = 'view';
		parent::__construct();

		if (!$this->module->active) {
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
		}
	}

	/**
	 * Redirect to the configuration screen of this module.
	 *
	 * {@inheritdoc}
	 */
	public function renderView()
	{
		Tools::redirectAdmin($this->container->get('router')->generate('admin_btcpay_configure'));
	}
}
