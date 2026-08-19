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
		$this->display_header = false;
		$this->display_footer = false;

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

		if (!$request->isMethod('POST')) {
			header('HTTP/1.1 405 Method Not Allowed');
			exit;
		}

		// If the module is inactive, or we don't receive a signature, or we don't have the webhook secret
		if (!$this->module->active
			|| null === ($signature = $request->headers->get(Constants::BTCPAY_HEADER_SIG))
			|| false === ($secret = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET))) {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}

		// Ensure the client is ready for use
		if (null === $this->client || false === $this->client->isValid()) {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}

		// Ensure our webhook is actually valid
		if (false === $this->client->webhook()->isIncomingWebhookRequestValid($request->getContent(), $signature, $secret)) {
			$error = 'Invalid BTCPay Server payment notification message received - signature did not match.';
			\PrestaShopLogger::addLog($error, \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR);
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}

		$this->handler->process($request);

		echo 'OK';
	}
}
