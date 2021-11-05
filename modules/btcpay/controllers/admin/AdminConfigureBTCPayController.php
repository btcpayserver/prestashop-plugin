<?php
class AdminConfigureBTCPayController extends ModuleAdminController
{
	/**
	 * @var BTCPay
	 */
	public $module;

	/**
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
	 * Redirect to the actual configuration screen of this module.
	 *
	 * {@inheritdoc}
	 */
	public function renderView()
	{
		Tools::redirectAdmin($this->container->get('router')->generate('admin_btcpay_configure'));
	}
}
