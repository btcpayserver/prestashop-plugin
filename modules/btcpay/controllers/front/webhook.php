<?php

use BTCPay\Constants;
use BTCPay\Server\Client;
use BTCPay\Server\Health;
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

	public function __construct()
	{
		// Webhook responses must not render the shop theme
		$this->display_header = false;
		$this->display_footer = false;

		parent::__construct();

		// The BTCPay client is created only after the signature has been verified (see postProcess):
		// an unconfigured module or an unreachable BTCPay Server must never turn into a PHP fatal error (500).
		$this->configuration = new Configuration();
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
	 * Status codes:
	 * - 405 not a POST request;
	 * - 400 module inactive/not configured, or signature header missing (BTCPay should not insist);
	 * - 401 wrong signature;
	 * - 503 signature valid but BTCPay Server not reachable/valid right now: BTCPay redelivers later;
	 * - 500 processing error (logged);
	 * - 200 processed, ignored or test delivery.
	 *
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
			$this->respond(405, 'Method Not Allowed');
		}

		$signature = (string) $request->headers->get(Constants::BTCPAY_HEADER_SIG, '');
		$secret    = (string) $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET);

		// If the module is inactive, or we don't receive a signature, or we don't have the webhook secret, just return
		if (!$this->module->active || '' === $signature || '' === $secret) {
			$this->respond(400, 'Bad Request');
		}

		// Grab the body of the request
		$body = $request->getContent();

		// Validate the HMAC first (locally, no BTCPay call needed), return a 401 error if the signature does not match
		if (!self::isSignatureValid($body, $signature, $secret)) {
			\PrestaShopLogger::addLog(
				'Invalid BTCPay Server payment notification message received - signature did not match.',
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);
			$this->respond(401, 'Unauthorized');
		}

		// Test deliveries ("Send test webhook" in BTCPay Server) do not need the API
		$data = \json_decode($body, true);
		if (\is_array($data) && isset($data['invoiceId']) && \is_string($data['invoiceId']) && \str_contains($data['invoiceId'], '__test__')) {
			\PrestaShopLogger::addLog('[INFO] Received BTCPay Server test webhook');
			$this->respond(200, 'OK');
		}

		// Ensure we can talk to BTCPay Server: the state is always re-read from the API, never trusted from the webhook.
		// A correctly signed delivery comes from BTCPay Server itself, so it is up again: close the "unreachable" window
		// (opened by an earlier outage) and try once, otherwise every redelivery during the window would get a 503 and
		// BTCPay Server would only retry minutes later. A failed attempt reopens the window (bounded by the HTTP timeouts).
		Health::markReachable();
		$client = Client::createFromConfiguration($this->configuration);
		if (null === $client || false === $client->isValid()) {
			\PrestaShopLogger::addLog(
				'[ERROR] A signed BTCPay webhook was received but BTCPay Server could not be queried (not linked, unreachable or invalid API key). BTCPay Server will redeliver it.',
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR
			);
			$this->respond(503, 'Service Unavailable');
		}

		// Try to process the webhook
		try {
			(new WebhookHandler($this->module, $this->context, $client))->process($request);
		} catch (\Throwable $throwable) {
			\PrestaShopLogger::addLog(
				\sprintf('[ERROR] Webhook processing failed: %s', $throwable->getMessage()),
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
				$throwable->getCode()
			);
			$this->respond(500, 'Internal Server Error');
		}

		$this->respond(200, 'OK');
	}

	/**
	 * BTCPay Server signs the raw body with HMAC-SHA256 and sends "sha256=<hex>" in the BTCPay-Sig header.
	 */
	public static function isSignatureValid(string $body, string $signature, string $secret): bool
	{
		return \BTCPayServer\Client\Webhook::isIncomingWebhookRequestValid($body, trim($signature), $secret);
	}

	/**
	 * Allow BTCPay IPNs while the shop is in maintenance mode.
	 */
	protected function displayMaintenancePage(): void
	{
	}

	/**
	 * Sends a plain text answer and stops the request.
	 */
	private function respond(int $code, string $text): void
	{
		\http_response_code($code);
		header('Content-Type: text/plain; charset=utf-8');
		echo $text;
		exit;
	}
}
