<?php

use BTCPay\Constants;
use BTCPay\LegacyBitcoinPaymentRepository;
use BTCPay\Server\Client;
use BTCPay\Server\WebhookHandler;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Request;

/** @noinspection AutoloadingIssuesInspection */
class BTCPayWebhookModuleFrontController extends \ModuleFrontController
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
	 * @var WebhookHandler
	 */
	private $handler;

	/**
	 * @var Configuration
	 */
	private $configuration;

	public function __construct()
	{
		parent::__construct();

		$this->configuration = new Configuration();
		$this->handler       = new WebhookHandler(Client::createFromConfiguration($this->configuration), new LegacyBitcoinPaymentRepository());
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
	 *
	 * @throws Exception
	 */
	public function postProcess(): void
	{
		$request = Request::createFromGlobals();

		// If the module is inactive, or we don't receive a signature, or we don't have the webhook secret, just return
		if (!$this->module->active
			|| null === ($signature = $request->headers->get(Constants::BTCPAY_HEADER_SIG))
			|| false === ($secret = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET))) {
			return;
		}

		// verify hmac256
		$content = $request->getContent();
		if ($signature !== 'sha256=' . hash_hmac('sha256', $content, $secret)) {
			throw new \Exception('Invalid BTCPayServer payment notification message received - signature did not match. Expected ' . hash_hmac('sha256', $content, $secret));
		}

		$this->handler->process($this->module, $request);

		echo 'OK';
	}
}
