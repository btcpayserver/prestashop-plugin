<?php

namespace BTCPay\Form\Data;

use BTCPay\Constants;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

class General
{
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
	private $protectOrders;

	/**
	 * @Assert\Choice(choices={true, false})
	 */
	private $shareMetadata;

	public function __construct(string $speed, string $orderMode, bool $protectOrders, bool $shareMetadata)
	{
		$this->speed         = $speed;
		$this->orderMode     = $orderMode;
		$this->protectOrders = $protectOrders;
		$this->shareMetadata = $shareMetadata;
	}

	public static function create(ShopConfigurationInterface $configuration): self
	{
		return new self(
			$configuration->get(Constants::CONFIGURATION_SPEED_MODE, InvoiceCheckoutOptions::SPEED_MEDIUM),
			$configuration->get(Constants::CONFIGURATION_ORDER_MODE, Constants::ORDER_MODE_BEFORE),
			(bool) $configuration->get(Constants::CONFIGURATION_PROTECT_ORDERS, true),
			(bool) $configuration->get(Constants::CONFIGURATION_SHARE_METADATA, false),
		);
	}

	public static function fromArray(array $data): self
	{
		return new self(
			$data['speed'],
			$data['orderMode'],
			$data['protectOrders'],
			$data['shareMetadata'],
		);
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

	public function getProtectOrders(): bool
	{
		return $this->protectOrders;
	}

	public function setProtectOrders(bool $protectOrders): void
	{
		$this->protectOrders = $protectOrders;
	}

	public function shareMetadata(): bool
	{
		return $this->shareMetadata;
	}

	public function setShareMetadata(bool $shareMetadata): void
	{
		$this->shareMetadata = $shareMetadata;
	}

	public function equals(self $general): bool
	{
		return $this->toArray() === $general->toArray();
	}

	public function toArray(): array
	{
		return [
			'speed'         => $this->speed,
			'orderMode'     => $this->orderMode,
			'protectOrders' => $this->protectOrders,
			'shareMetadata' => $this->shareMetadata,
		];
	}
}
