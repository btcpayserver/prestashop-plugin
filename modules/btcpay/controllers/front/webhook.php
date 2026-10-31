<?php

use BTCPay\Constants;
use BTCPay\Server\Client;
use BTCPay\Server\CurlAdapter;
use BTCPay\Server\WebhookHandler;
use BTCPay\Server\WebhookRequestGuard;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
	 * - 400 module inactive/not configured, or signature header missing;
	 * - 401 wrong signature;
	 * - 503 signature valid but BTCPay Server not reachable/valid: BTCPay redelivers later;
	 * - 500 processing error (logged);
	 * - 200 processed, ignored or test delivery.
	 *
	 * {@inheritdoc}
	 *
	 * @throws Exception
	 */
	public function postProcess(): void
	{
		$request = Request::createFromGlobals();

		if (!$request->isMethod(Request::METHOD_POST)) {
			$this->respond(Response::HTTP_METHOD_NOT_ALLOWED, 'Method Not Allowed');
		}

		// BTCPay sends JSON; reject other media types before parsing the body
		if (false === WebhookRequestGuard::hasJsonContentType($request->headers->get('Content-Type'))) {
			$this->respond(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'Unsupported Media Type');
		}

		// Reject oversized requests before reading the body when the size is declared
		$declaredLength = $request->headers->get('Content-Length');
		if (null !== $declaredLength && \ctype_digit((string) $declaredLength) && false === WebhookRequestGuard::isWithinBodyLimit((int) $declaredLength)) {
			$this->respond(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'Payload Too Large');
		}

		$signature = (string) $request->headers->get(Constants::BTCPAY_HEADER_SIG, '');
		$secret    = (string) $this->configuration->get(Constants::CONFIGURATION_BTCPAY_WEBHOOK_SECRET);

		if (!$this->module->active || '' === $signature || '' === $secret) {
			$this->respond(Response::HTTP_BAD_REQUEST, 'Bad Request');
		}

		$body = $request->getContent();

		// Enforce the body limit when no usable Content-Length header was supplied
		if (false === WebhookRequestGuard::isWithinBodyLimit(\strlen($body))) {
			$this->respond(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'Payload Too Large');
		}

		if (!self::isSignatureValid($body, $signature, $secret)) {
			\PrestaShopLogger::addLog(
				'Invalid BTCPay Server payment notification message received - signature did not match.',
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);
			$this->respond(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
		}

		// Reject malformed JSON before handing the request to the handler
		if (false === WebhookRequestGuard::isValidJson($body)) {
			\PrestaShopLogger::addLog(
				'Invalid BTCPay Server payment notification message received - body is not a JSON object.',
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);
			$this->respond(Response::HTTP_BAD_REQUEST, 'Bad Request');
		}

		$data = \json_decode($body, true);
		if (\is_array($data) && isset($data['invoiceId']) && \is_string($data['invoiceId']) && \str_contains($data['invoiceId'], '__test__')) {
			\PrestaShopLogger::addLog('[INFO] Received BTCPay Server test webhook');
			$this->respond(Response::HTTP_OK, 'OK');
		}

		// A correctly signed delivery means BTCPay is up again: close the unreachable pause and try once
		CurlAdapter::resume();
		$client = Client::createFromConfiguration($this->configuration);
		if (null === $client || false === $client->isValid()) {
			\PrestaShopLogger::addLog(
				'[ERROR] A signed BTCPay webhook was received but BTCPay Server could not be queried. BTCPay Server will redeliver it.',
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR
			);
			$this->respond(Response::HTTP_SERVICE_UNAVAILABLE, 'Service Unavailable');
		}

		try {
			(new WebhookHandler($this->module, $this->context, $client))->process($request);
		} catch (\Throwable $throwable) {
			\PrestaShopLogger::addLog(
				\sprintf('[ERROR] Webhook processing failed: %s', $throwable->getMessage()),
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
				$throwable->getCode()
			);
			$this->respond(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal Server Error');
		}

		$this->respond(Response::HTTP_OK, 'OK');
	}

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

	private function respond(int $code, string $text): void
	{
		\http_response_code($code);
		header('Content-Type: text/plain; charset=utf-8');
		echo $text;
		exit;
	}
}
