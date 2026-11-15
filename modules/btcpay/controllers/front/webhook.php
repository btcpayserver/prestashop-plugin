<?php

declare(strict_types=1);

use BTCPay\Server\WebhookReceiver;
use Symfony\Component\HttpFoundation\Request;

if (!defined('_PS_VERSION_')) {
	exit;
}

final class BTCPayWebhookModuleFrontController extends ModuleFrontController
{
	/**
	 * Enable SSL only.
	 */
	public bool $ssl = true;

	/** @var BTCPay */
	public $module;

	public function __construct()
	{
		// Webhook responses must not render the shop theme
		$this->display_header = false;
		$this->display_footer = false;

		parent::__construct();
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

	public function postProcess(): void
	{
		/** @var WebhookReceiver $receiver */
		$receiver = $this->module->get(WebhookReceiver::class);
		$result = $receiver->handle(Request::createFromGlobals());
		$this->respond($result['code'], $result['text']);
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
