<?php

use BTCPay\Constants;
use BTCPay\Server\Client;
use BTCPay\Server\WebhookHandler;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Request;

if (!defined('_PS_VERSION_')) {
	exit;
}

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
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var Client|null
	 */
	private $client;

	/**
	 * @var WebhookHandler
	 */
	private $handler;

	public function __construct()
	{
		parent::__construct();

		$this->configuration = new Configuration();
		$this->client        = Client::createFromConfiguration($this->configuration);
		$this->handler       = new WebhookHandler($this->module, $this->context, $this->client);
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

		// Ensure the client is ready for use, if not, just return
		if (null === $this->client || false === $this->client->isValid()) {
			return;
		}

		// Ensure our webhook is actually valid
		if (false === $this->client->webhook()->isIncomingWebhookRequestValid($request->getContent(), $signature, $secret)) {
			$error = 'Invalid BTCPay Server payment notification message received - signature did not match.';
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);

			throw new \Exception($error);
		}

		$this->handler->process($request);

		echo 'OK';
	}
}
