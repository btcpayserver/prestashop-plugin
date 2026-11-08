<?php

namespace BTCPay\Installer;

use BTCPay\Constants;
use Language;
use OrderState;
use PrestaShop\PrestaShop\Adapter\Configuration;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class OrderStates
{
	/**
	 * @var string
	 */
	private $moduleName;

	/**
	 * @var Configuration
	 */
	private $configuration;

	public function __construct(string $moduleName)
	{
		$this->moduleName    = $moduleName;
		$this->configuration = new Configuration();
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \Exception
	 */
	public function install(): array
	{
		$errors = [];

		// Check and insert "awaiting payment" if needed.
		if (!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING)
			|| !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING)))) {
			if (false === $this->installAwaiting()) {
				$errors[] = [
					'key'        => \sprintf('Could not add new order state: %s', Constants::CONFIGURATION_ORDER_STATE_WAITING),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}
		}

		// Check and insert "confirming payment" if needed.
		if (!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING)
			|| !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING)))) {
			if (false === $this->installConfirming()) {
				$errors[] = [
					'key'        => \sprintf('Could not add new order state: %s', Constants::CONFIGURATION_ORDER_STATE_CONFIRMING),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}
		}

		// Check and insert "failed payment" if needed.
		if (!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED)
			|| !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED)))) {
			if (false === $this->installFailed()) {
				$errors[] = [
					'key'        => \sprintf('Could not add new order state: %s', Constants::CONFIGURATION_ORDER_STATE_FAILED),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}
		}

		// Check and insert "payment succeeded" if needed.
		if (!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID)
			|| !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID)))) {
			if (false === $this->installPaid()) {
				$errors[] = [
					'key'        => \sprintf('Could not add new order state: %s', Constants::CONFIGURATION_ORDER_STATE_PAID),
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}
		}

		return $errors;
	}

	/**
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 * @throws \Exception
	 */
	private function installAwaiting(): bool
	{
		$order_state              = new OrderState();
		$order_state->color       = '#FF8C00';
		$order_state->unremovable = true;
		$order_state->module_name = $this->moduleName;
		$order_state->name        = [];
		foreach (Language::getLanguages(true) as $language) {
			$order_state->name[(int) $language['id_lang']] = $this->translate('Awaiting crypto payment', (string) ($language['locale'] ?? ''));
		}

		if (false === $order_state->add()) {
			return false;
		}

		$this->installImage($order_state, 'os_bitcoin_waiting.png');
		$this->configuration->set(Constants::CONFIGURATION_ORDER_STATE_WAITING, (int) $order_state->id);

		return true;
	}

	/**
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 * @throws \Exception
	 */
	private function installConfirming(): bool
	{
		$order_state              = new OrderState();
		$order_state->color       = '#4169E1';
		$order_state->unremovable = true;
		$order_state->module_name = $this->moduleName;
		$order_state->name        = [];
		foreach (Language::getLanguages(true) as $language) {
			$order_state->name[(int) $language['id_lang']] = $this->translate('Waiting for confirmations', (string) ($language['locale'] ?? ''));
		}

		if (false === $order_state->add()) {
			return false;
		}

		$this->installImage($order_state, 'os_bitcoin_confirming.png');
		$this->configuration->set(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING, (int) $order_state->id);

		return true;
	}

	/**
	 * @throws \PrestaShopDatabaseException
	 * @throws \PrestaShopException
	 * @throws \Exception
	 */
	private function installFailed(): bool
	{
		$order_state              = new OrderState();
		// No email here: failure is followed by PrestaShop "Canceled", which emails the customer
		$order_state->send_email  = false;
		$order_state->color       = '#EC2E15';
		// Not a sale: a failed/expired invoice must not count in the statistics
		$order_state->logable     = false;
		$order_state->unremovable = true;
		$order_state->module_name = $this->moduleName;
		$order_state->name        = [];
		foreach (Language::getLanguages(true) as $language) {
			$order_state->name[(int) $language['id_lang']] = $this->translate('Crypto transaction failed', (string) ($language['locale'] ?? ''));
		}

		if (false === $order_state->add()) {
			return false;
		}

		$this->installImage($order_state, 'os_bitcoin_failed.png');
		$this->configuration->set(Constants::CONFIGURATION_ORDER_STATE_FAILED, (int) $order_state->id);

		return true;
	}

	/**
	 * @throws \PrestaShopException
	 * @throws \PrestaShopDatabaseException
	 * @throws \Exception
	 */
	private function installPaid(): bool
	{
		$order_state              = new OrderState();
		$order_state->paid        = true;
		$order_state->pdf_invoice = true;
		$order_state->send_email  = true;
		$order_state->template    = 'payment';
		$order_state->color       = '#108510';
		$order_state->logable     = true;
		$order_state->unremovable = true;
		$order_state->invoice     = true;
		$order_state->module_name = $this->moduleName;
		$order_state->name        = [];
		foreach (Language::getLanguages(true) as $language) {
			$order_state->name[(int) $language['id_lang']] = $this->translate('Paid with crypto', (string) ($language['locale'] ?? ''));
		}

		if (false === $order_state->add()) {
			return false;
		}

		$this->installImage($order_state, 'os_bitcoin_paid.png');
		$this->configuration->set(Constants::CONFIGURATION_ORDER_STATE_PAID, (int) $order_state->id);

		return true;
	}

	private function installImage(OrderState $order_state, string $image_name): void
	{
		$source      = \_PS_MODULE_DIR_ . $this->moduleName . '/views/images/' . $image_name;
		$destination = \_PS_ROOT_DIR_ . '/img/os/' . (int) $order_state->id . '.gif';
		\copy($source, $destination);
	}

	/**
	 * Replace a stock English name with that language's translation. A name that no longer matches the English source is left as written.
	 */
	public function localizeExisting(OrderState $orderState): void
	{
		if (!\is_array($orderState->name)) {
			return;
		}

		foreach (Language::getLanguages(true) as $language) {
			$id      = (int) $language['id_lang'];
			$current = (string) ($orderState->name[$id] ?? '');
			if (!\in_array($current, [
				'Awaiting crypto payment',
				'Waiting for confirmations',
				'Crypto transaction failed',
				'Paid with crypto',
			], true)) {
				continue;
			}
			$orderState->name[$id] = $this->translate($current, (string) ($language['locale'] ?? ''));
		}
	}

	private function translate(string $message, string $locale): string
	{
		if ($locale === '') {
			return $message;
		}

		return (string) \Context::getContext()->getTranslator()->trans($message, [], 'Modules.Btcpay.Admin', $locale);
	}
}
