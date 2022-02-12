<?php

namespace BTCPay\Form;

use BTCPay\Constants;
use BTCPay\Form\Data\Configuration;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use PrestaShop\PrestaShop\Adapter\Configuration as PrestaShopConfiguration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class ConfigureFormDataProvider implements FormDataProviderInterface
{
	/**
	 * @var PrestaShopConfiguration
	 */
	private $configuration;

	public function __construct()
	{
		$this->configuration = new PrestaShopConfiguration();
	}

	/**
	 * @return Configuration[]
	 */
	public function getData(): array
	{
		$configuration = new Configuration(
			$this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST, Constants::CONFIGURATION_DEFAULT_HOST),
			$this->configuration->get(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM),
			$this->configuration->get(Constants::CONFIGURATION_ORDER_MODE, Constants::ORDER_MODE_BEFORE),
			$this->configuration->get(Constants::CONFIGURATION_SHARE_METADATA, false),
		);

		return ['btcpay' => $configuration];
	}

	/**
	 * @throws \Exception
	 */
	public function setData(array $data): array
	{
		/** @var Configuration $configuration */
		$configuration = $data['btcpay'];

		$this->configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, \rtrim(\trim($configuration->getUrl()), '/\\'));
		$this->configuration->set(Constants::CONFIGURATION_SPEED_MODE, $configuration->getSpeed());
		$this->configuration->set(Constants::CONFIGURATION_ORDER_MODE, $configuration->getOrderMode());
		$this->configuration->set(Constants::CONFIGURATION_SHARE_METADATA, $configuration->shareMetadata());

		// All is fine
		return [];
	}
}
