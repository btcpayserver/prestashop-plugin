<?php

declare(strict_types=1);

namespace BTCPay\Form;

use BTCPay\Constants;
use BTCPay\Form\Data\Server;
use Exception;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

if (!\defined('_PS_VERSION_')) {
	exit;
}

final class ServerFormDataProvider implements FormDataProviderInterface
{
	public function __construct(private readonly ShopConfigurationInterface $configuration)
	{
	}

	public function getData(): Server
	{
		return Server::create($this->configuration);
	}

	/**
	 * @throws Exception
	 */
	public function setData(array $data): array
	{
		// Re-create configuration element with form data
		$configuration = Server::fromArray($data);

		if ($this->configuration->get(Constants::CONFIGURATION_BTCPAY_HOST) !== \rtrim(\trim($host = $configuration->getHost()), '/\\') && !empty($host)) {
			$this->configuration->set(Constants::CONFIGURATION_BTCPAY_HOST, \rtrim(\trim($host), '/\\'));
		}

		if ($this->configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY) !== \rtrim(\trim($apiKey = $configuration->getApiKey()), '/\\') && !empty($apiKey)) {
			$this->configuration->set(Constants::CONFIGURATION_BTCPAY_API_KEY, \rtrim(\trim($apiKey), '/\\'));
		}

		// All is fine
		return [];
	}
}
