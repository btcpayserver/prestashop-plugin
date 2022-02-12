<?php

namespace BTCPay\Installer;

use BTCPay\Constants;
use Language;
use OrderState;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShopCollection;

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
					'key'        => 'Could not add new order state: BTCPAY_OS_WAITING',
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
					'key'        => 'Could not add new order state: BTCPAY_OS_CONFIRMING',
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
					'key'        => 'Could not add new order state: BTCPAY_OS_FAILED',
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
					'key'        => 'Could not add new order state: BTCPAY_OS_PAID',
					'parameters' => [],
					'domain'     => 'Admin.Modules.Notification',
				];
			}
		}

		return $errors;
	}

	/**
	 * @throws \PrestaShopException
	 */
	public function uninstall(): array
	{
		$collection = new PrestaShopCollection('OrderState');
		$collection->where('module_name', '=', $this->moduleName);

		if (empty($orderStates = $collection->getResults())) {
			return [];
		}

		$errors = [];

		/** @var OrderState $orderState */
		foreach ($orderStates as $orderState) {
			if (false === $orderState->delete()) {
				$errors[] = [
					'key'        => 'Could not delete order state ' . $orderState->name,
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
		$order_state->name        = [];
		$order_state->color       = '#FF8C00';
		$order_state->unremovable = true;
		$order_state->module_name = $this->moduleName;

		foreach (Language::getLanguages(true, false, true) as $languageId) {
			$order_state->name[$languageId] = 'Awaiting crypto payment';
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
		$order_state->name        = [];
		$order_state->color       = '#4169E1';
		$order_state->unremovable = true;
		$order_state->module_name = $this->moduleName;

		foreach (Language::getLanguages(true, false, true) as $languageId) {
			$order_state->name[$languageId] = 'Waiting for confirmations';
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
		$order_state->name        = [];
		$order_state->send_email  = true;
		$order_state->template    = 'payment_error';
		$order_state->color       = '#EC2E15';
		$order_state->logable     = true;
		$order_state->unremovable = true;
		$order_state->module_name = $this->moduleName;

		foreach (Language::getLanguages(true, false, true) as $languageId) {
			$order_state->name[$languageId] = 'Crypto transaction failed';
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
		$order_state->name        = [];
		$order_state->paid        = true;
		$order_state->pdf_invoice = true;
		$order_state->send_email  = true;
		$order_state->template    = 'payment';
		$order_state->color       = '#108510';
		$order_state->logable     = true;
		$order_state->unremovable = true;
		$order_state->module_name = $this->moduleName;

		foreach (Language::getLanguages(true, false, true) as $languageId) {
			$order_state->name[$languageId] = 'Paid with crypto';
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
}
