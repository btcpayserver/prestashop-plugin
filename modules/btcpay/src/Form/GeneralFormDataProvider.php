<?php

namespace BTCPay\Form;

use BTCPay\Constants;
use BTCPay\Form\Data\General;
use PrestaShop\PrestaShop\Adapter\Configuration as PrestaShopConfiguration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class GeneralFormDataProvider implements FormDataProviderInterface
{
	/**
	 * @var PrestaShopConfiguration
	 */
	private $configuration;

	public function __construct()
	{
		$this->configuration = new PrestaShopConfiguration();
	}

	public function getData(): General
	{
		return General::create($this->configuration);
	}

	/**
	 * @throws \Exception
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

		// All is fine
		return [];
	}
}
