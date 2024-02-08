<?php

namespace BTCPay\Form\Data;

use BTCPay\Constants;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Configuration
{
	/**
	 * @Assert\Url()
	 * @Assert\NotBlank()
	 *
	 * @var string|null
	 */
	private $host;

	/**
	 * @Assert\NotBlank()
	 * @Assert\Choice(choices=\BTCPay\Constants::TRANSACTION_SPEEDS, message="Invalid transaction speed")
	 *
	 * @var string
	 */
	private $speed;

	/**
	 * @Assert\NotBlank()
	 * @Assert\Choice(choices=\BTCPay\Constants::ORDER_MODES, message="Invalid order mode")
	 *
	 * @var string
	 */
	private $orderMode;

	/**
	 * @Assert\Choice(choices={true, false})
	 */
	private $shareMetadata;

	/**
	 * @Assert\Type(type="alnum")
	 *
	 * @var string|null
	 */
	private $apiKey;

	public function __construct(string $host, string $speed, string $orderMode, bool $shareMetadata, string $apiKey = null)
	{
		$this->host = $host;
		$this->apiKey = $apiKey;
		$this->speed = $speed;
		$this->orderMode = $orderMode;
		$this->shareMetadata = $shareMetadata;
	}

	public static function create(ShopConfigurationInterface $configuration): self
	{
		return new self(
			$configuration->get(Constants::CONFIGURATION_BTCPAY_HOST, Constants::CONFIGURATION_DEFAULT_HOST),
			$configuration->get(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM),
			$configuration->get(Constants::CONFIGURATION_ORDER_MODE, Constants::ORDER_MODE_BEFORE),
			(bool) $configuration->get(Constants::CONFIGURATION_SHARE_METADATA, false),
			$configuration->get(Constants::CONFIGURATION_BTCPAY_API_KEY, null),
		);
	}

	public static function fromArray(array $data): self
	{
		return new self(
			$data['host'],
			$data['speed'],
			$data['orderMode'],
			$data['shareMetadata'],
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

	public function getSpeed(): string
	{
		return $this->speed;
	}

	public function setSpeed(string $speed): void
	{
		$this->speed = $speed;
	}

	public function getOrderMode(): string
	{
		return $this->orderMode;
	}

	public function setOrderMode(string $order_mode): void
	{
		$this->orderMode = $order_mode;
	}

	public function shareMetadata(): bool
	{
		return $this->shareMetadata;
	}

	public function setShareMetadata(bool $shareMetadata): void
	{
		$this->shareMetadata = $shareMetadata;
	}

	public function equals(self $configuration): bool
	{
		return $this->toArray() === $configuration->toArray();
	}

	public function toArray(): array
	{
		return [
			'host'          => $this->host,
			'speed'         => $this->speed,
			'orderMode'     => $this->orderMode,
			'shareMetadata' => $this->shareMetadata,
			'apiKey'        => $this->apiKey,
		];
	}
}
