<?php

use BTCPay\LegacyOrderBitcoinRepository;
use BTCPay\Server\IPN;
use Symfony\Component\HttpFoundation\Request;

/** @noinspection AutoloadingIssuesInspection */
class BTCPayIpnModuleFrontController extends \ModuleFrontController
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
	 * @var IPN
	 */
	private $ipn;

	public function __construct()
	{
		parent::__construct();

		$this->ipn = new IPN(new LegacyOrderBitcoinRepository());
	}

	/**
	 * We don't want to show anything, so just don't render anything.
	 *
	 * {@inheritdoc}
	 */
	public function display(): bool
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function postProcess(): void
	{
		try {
			$this->ipn->process($this->module, Request::createFromGlobals());
		} catch (\Exception $e) {
			throw new LogicException('Could not process', 1, $e);
		}
	}
}
