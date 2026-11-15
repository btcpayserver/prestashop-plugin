<?php

declare(strict_types=1);

namespace BTCPay\Installer;

use BTCPay\Constants;
use Context;
use Language;
use OrderState;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShop\PrestaShop\Core\Domain\OrderState\Command\AddOrderStateCommand;
use PrestaShop\PrestaShop\Core\Domain\OrderState\Command\EditOrderStateCommand;
use PrestaShop\PrestaShop\Core\Domain\OrderState\ValueObject\OrderStateId;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Throwable;
use Validate;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class OrderStates
{
	public function __construct(
		private readonly string $moduleName,
		private readonly CommandBusInterface $commandBus,
		private readonly ShopConfigurationInterface $configuration,
	) {
	}

	/**
	 * @return list<array{key: string, parameters: array<mixed>, domain: string}>
	 */
	public function install(): array
	{
		$errors = [];

		// Check and insert "awaiting payment" if needed.
		if (
			!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING)
			|| !$this->isLoaded((int) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_WAITING))
		) {
			if (false === $this->installAwaiting()) {
				$errors[] = $this->error(Constants::CONFIGURATION_ORDER_STATE_WAITING);
			}
		}

		// Check and insert "confirming payment" if needed.
		if (
			!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING)
			|| !$this->isLoaded((int) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING))
		) {
			if (false === $this->installConfirming()) {
				$errors[] = $this->error(Constants::CONFIGURATION_ORDER_STATE_CONFIRMING);
			}
		}

		// Check and insert "failed payment" if needed.
		if (
			!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED)
			|| !$this->isLoaded((int) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED))
		) {
			if (false === $this->installFailed()) {
				$errors[] = $this->error(Constants::CONFIGURATION_ORDER_STATE_FAILED);
			}
		}

		// Check and insert "payment succeeded" if needed.
		if (
			!$this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID)
			|| !$this->isLoaded((int) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID))
		) {
			if (false === $this->installPaid()) {
				$errors[] = $this->error(Constants::CONFIGURATION_ORDER_STATE_PAID);
			}
		}

		return $errors;
	}

	public function synchronizeExisting(): bool
	{
		// Update "paid with crypto" order statuses to allow the invoice to be downloaded
		$paidId = (int) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_PAID);
		// Ensure we actually have a valid order state
		if ($this->isLoaded($paidId)) {
			// Allow the invoice to be downloaded and save the order state
			$this->commandBus->handle((new EditOrderStateCommand($paidId))->setInvoice(true));
		}

		// Failed/expired invoices now move on to PrestaShop "Canceled" (restock + email)
		$failedId = (int) $this->configuration->get(Constants::CONFIGURATION_ORDER_STATE_FAILED);
		if ($this->isLoaded($failedId)) {
			$this->commandBus->handle((new EditOrderStateCommand($failedId))->setLoggable(false)->setSendEmail(false));
		}

		// 6.4.3 ran this before the module-file fallback existed, so shops on 6.4.3 still have English names.
		foreach (
			[
				Constants::CONFIGURATION_ORDER_STATE_WAITING,
				Constants::CONFIGURATION_ORDER_STATE_CONFIRMING,
				Constants::CONFIGURATION_ORDER_STATE_FAILED,
				Constants::CONFIGURATION_ORDER_STATE_PAID,
			] as $configurationKey
		) {
			$id = (int) $this->configuration->get($configurationKey);
			if ($this->isLoaded($id)) {
				$this->localizeExisting($id);
			}
		}

		return true;
	}

	public function localizeExisting(int $orderStateId): void
	{
		$orderState = new OrderState($orderStateId);
		if (!$this->isLoaded($orderStateId)) {
			return;
		}

		$names = [];
		$changed = false;
		foreach (Language::getLanguages(true) as $language) {
			$id = (int) $language['id_lang'];
			$current = (string) ($orderState->name[$id] ?? '');
			$names[$id] = $current;
			if (
				!\in_array($current, [
					'Awaiting crypto payment',
					'Waiting for confirmations',
					'Crypto transaction failed',
					'Paid with crypto',
				], true)
			) {
				continue;
			}

			$translated = $this->translate($current, (string) $language['locale']);
			if ($translated === $current) {
				// During an upgrade the translator still holds the catalogue of the previous version
				$translated = $this->translateFromModuleFile($current, (string) $language['locale']);
			}
			if ($translated !== $current) {
				$names[$id] = $translated;
				$changed = true;
			}
		}

		if ($changed) {
			$this->commandBus->handle((new EditOrderStateCommand($orderStateId))->setName($names));
		}
	}

	private function installAwaiting(): bool
	{
		return $this->addState(
			$this->names('Awaiting crypto payment'),
			'#FF8C00',
			false,
			false,
			false,
			false,
			false,
			'os_bitcoin_waiting.png',
			Constants::CONFIGURATION_ORDER_STATE_WAITING,
		);
	}

	private function installConfirming(): bool
	{
		return $this->addState(
			$this->names('Waiting for confirmations'),
			'#4169E1',
			false,
			false,
			false,
			false,
			false,
			'os_bitcoin_confirming.png',
			Constants::CONFIGURATION_ORDER_STATE_CONFIRMING,
		);
	}

	private function installFailed(): bool
	{
		return $this->addState(
			$this->names('Crypto transaction failed'),
			'#EC2E15',
			// Not a sale: a failed/expired invoice must not count in the statistics
			false,
			false,
			// No email here: failure is followed by PrestaShop "Canceled", which emails the customer
			false,
			false,
			false,
			'os_bitcoin_failed.png',
			Constants::CONFIGURATION_ORDER_STATE_FAILED,
		);
	}

	private function installPaid(): bool
	{
		$templates = [];
		foreach (Language::getLanguages(true) as $language) {
			$templates[(int) $language['id_lang']] = 'payment';
		}

		return $this->addState(
			$this->names('Paid with crypto'),
			'#108510',
			true,
			true,
			true,
			true,
			true,
			'os_bitcoin_paid.png',
			Constants::CONFIGURATION_ORDER_STATE_PAID,
			$templates,
		);
	}

	/**
	 * @param array<int, string> $names
	 * @param array<int, string> $templates
	 */
	private function addState(
		array $names,
		string $color,
		bool $loggable,
		bool $invoice,
		bool $sendEmail,
		bool $pdfInvoice,
		bool $paid,
		string $imageName,
		string $configurationKey,
		array $templates = [],
	): bool {
		try {
			$command = new AddOrderStateCommand($names, $color, $loggable, $invoice, false, $sendEmail, $pdfInvoice, false, false, $paid, false, $templates);
			$path = \_PS_MODULE_DIR_ . $this->moduleName . '/views/images/' . $imageName;
			$command->setFileInformation($path, (int) \filesize($path), (string) \mime_content_type($path), $imageName);
			$result = $this->commandBus->handle($command);
			$id = $result instanceof OrderStateId ? $result->getValue() : (int) $result;
			$this->configuration->set($configurationKey, $id);
		} catch (Throwable) {
			return false;
		}

		return true;
	}

	/**
	 * @return array<int, string>
	 */
	private function names(string $message): array
	{
		$names = [];
		foreach (Language::getLanguages(true) as $language) {
			$names[(int) $language['id_lang']] = $this->translate($message, (string) $language['locale']);
		}

		return $names;
	}

	private function isLoaded(int $orderStateId): bool
	{
		return $orderStateId > 0 && Validate::isLoadedObject(new OrderState($orderStateId));
	}

	/**
	 * @return array{key: string, parameters: array<mixed>, domain: string}
	 */
	private function error(string $key): array
	{
		return [
			'key' => \sprintf('Could not add new order state: %s', $key),
			'parameters' => [],
			'domain' => 'Admin.Modules.Notification',
		];
	}

	private function translate(string $message, string $locale): string
	{
		return (string) Context::getContext()->getTranslator()->trans($message, [], 'Modules.Btcpay.Admin', $locale);
	}

	private function translateFromModuleFile(string $message, string $locale): string
	{
		if (!Validate::isLanguageCode($locale)) {
			return $message;
		}

		$file = \_PS_MODULE_DIR_ . $this->moduleName . '/translations/' . $locale . '/ModulesBtcpayAdmin.' . $locale . '.xlf';
		if (!\is_file($file)) {
			return $message;
		}

		try {
			$catalogue = (new XliffFileLoader())->load($file, $locale, 'Modules.Btcpay.Admin');
		} catch (Throwable) {
			return $message;
		}

		return $catalogue->has($message, 'Modules.Btcpay.Admin') ? (string) $catalogue->get($message, 'Modules.Btcpay.Admin') : $message;
	}
}
