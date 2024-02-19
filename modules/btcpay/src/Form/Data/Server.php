<?php

namespace BTCPay\Form\Data;

use BTCPay\Constants;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

if (!\defined('_PS_VERSION_')) {
	exit;
}

class Server
{
	/**
	 * @Assert\Url()
	 * @Assert\NotBlank()
	 *
	 * @var string|null
	 */
	private $host;

	/**
	 * @Assert\Type(type="alnum")
	 *
	 * @var string|null
	 */
	private $apiKey;

	public function __construct(string $host, string $apiKey = null)
	{
		$this->host          = $host;
		$this->apiKey        = $apiKey;
	}

	public static function create(ShopConfigurationInterface $configuration): self
	{
		return new self(
			$configuration->get(Constants::CONFIGURATION_BTCPAY_HOST, Constants::CONFIGURATION_DEFAULT_HOST),
			$configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY, null),
		);
	}

	public static function fromArray(array $data): self
	{
		return new self(
			$data['host'],
			$data['apiKey'],
		);
	}

	public function getHost(): ?string
	{
		return $this->host;
	}

	public function setHost(?string $host): void
	{
		$this->host = $host;
	}

	public function getApiKey(): ?string
	{
		return $this->apiKey;
	}

	public function setApiKey(?string $apiKey): void
	{
		$this->apiKey = $apiKey;
	}

	public function equals(self $configuration): bool
	{
		return $this->toArray() === $configuration->toArray();
	}

	public function toArray(): array
	{
		return [
			'host'   => $this->host,
			'apiKey' => $this->apiKey,
		];
	}
}
