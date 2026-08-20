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
		// Webhook responses must not render the shop theme
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
		// Create the request
		$request = Request::createFromGlobals();

		// BTCPay webhooks are POST-only, reject anything else
		if (!$request->isMethod('POST')) {
			header('HTTP/1.1 405 Method Not Allowed');
			exit;
		}

		// If the module is inactive, or we don't receive a signature, or we don't have the webhook secret, just return
		if (!$this->module->active
			|| null === ($signature = $request->headers->get(Constants::BTCPAY_HEADER_SIG))
			|| false === ($secret = $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET))) {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}

		// Grab the body of the request
		$body = $request->getContent();

		// Validate the HMAC first, log the error and return a 401 error if the signature does not match
		if (false === $this->client->webhook()->isIncomingWebhookRequestValid($request->getContent(), $signature, $secret)) {
			\PrestaShopLogger::addLog(
				'Invalid BTCPay Server payment notification message received - signature did not match.',
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}

		// Ensure our webhook is actually valid
		if (null === $this->client || false === $this->client->isValid()) {
			\PrestaShopLogger::addLog(
				'[ERROR] A signed BTCPay webhook was received but the module could not create a valid BTCPay Server client. Check that the store is still linked and the API key is valid.',
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR
			);
			header('HTTP/1.1 400 Bad Request');
			exit;
		}

		// Try to process the webhook
		try {
			$this->handler->process($request);
		} catch (\Throwable $throwable) {
			\PrestaShopLogger::addLog(
				\sprintf('[ERROR] Webhook processing failed: %s', $throwable->getMessage()),
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
				$throwable->getCode()
			);
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}

		echo 'OK';
		exit;
	}

	/**
	 * Allow BTCPay IPNs while the shop is in maintenance mode.
	 */
	protected function displayMaintenancePage(): void
	{
	}
}
