<?php

declare(strict_types=1);

namespace BTCPay\Form;

use BTCPay\Constants;
use BTCPay\Form\Data\General;
use Exception;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class GeneralFormDataProvider implements FormDataProviderInterface
{
	public function __construct(private readonly ShopConfigurationInterface $configuration)
	{
	}

	public function getData(): General
	{
		return General::create($this->configuration);
	}

	/**
	 * @throws Exception
	 */
	public function setData(array $data): array
	{
		// Re-create configuration element with form data
		$configuration = General::fromArray($data);

		if ($this->configuration->get(Constants::CONFIGURATION_SPEED_MODE) !== ($speedMode = $configuration->getSpeed()) && !empty($speedMode)) {
			$this->configuration->set(Constants::CONFIGURATION_SPEED_MODE, $speedMode);
		}

		if ($this->configuration->get(Constants::CONFIGURATION_ORDER_MODE) !== ($orderMode = $configuration->getOrderMode()) && !empty($orderMode)) {
			$this->configuration->set(Constants::CONFIGURATION_ORDER_MODE, $orderMode);
		}

		if ($this->configuration->get(Constants::CONFIGURATION_SHARE_METADATA) !== ($shareMetadata = $configuration->shareMetadata())) {
			$this->configuration->set(Constants::CONFIGURATION_SHARE_METADATA, $shareMetadata);
		}

		if ($this->configuration->get(Constants::CONFIGURATION_PROTECT_ORDERS) !== ($protectOrders = $configuration->getProtectOrders())) {
			$this->configuration->set(Constants::CONFIGURATION_PROTECT_ORDERS, $protectOrders);
		}

		// All is fine
		return [];
	}
}
