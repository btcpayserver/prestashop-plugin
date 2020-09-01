<?php

namespace BTCPay\Form\Data;

use BTCPayServer\Invoice;
use Symfony\Component\Validator\Constraints as Assert;

class Configuration
{
	/**
	 * All possible transaction speeds as defined by BTCPay server
	 */
	public const TRANSACTION_SPEEDS = [Invoice::TRANSACTION_SPEED_LOW, Invoice::TRANSACTION_SPEED_MEDIUM, Invoice::TRANSACTION_SPEED_HIGH];

	/**
	 * All possible options for order creation (before or after payment)
	 */
	public const ORDER_MODES       = [self::ORDER_MODE_BEFORE, self::ORDER_MODE_AFTER];
	public const ORDER_MODE_BEFORE = 'beforepayment';
	public const ORDER_MODE_AFTER  = 'afterpayment';

	/**
	 * @Assert\Url()
	 * @Assert\NotNull()
	 * @Assert\NotBlank()
	 *
	 * @var string
	 */
	private $url;

	/**
	 * @Assert\NotNull()
	 * @Assert\NotBlank()
	 * @Assert\Choice(choices=Configuration::TRANSACTION_SPEEDS, message="Invalid transaction speed")
	 *
	 * @var string
	 */
	private $transactionSpeed;

	/**
	 * @Assert\NotNull()
	 * @Assert\NotBlank()
	 * @Assert\Choice(choices=Configuration::ORDER_MODES, message="Invalid order mode")
	 *
	 * @var string
	 */
	private $orderMode;

	/**
	 * @Assert\NotBlank()
	 * @Assert\Regex(pattern="/^[a-zA-Z0-9]{7}$/", message="Invalid pairing code")
	 *
	 * @var string|null
	 */
	private $pairingCode;

	public function __construct(string $url, string $transactionSpeed, string $orderMode, ?string $pairingCode)
	{
		$this->url              = $url;
		$this->transactionSpeed = $transactionSpeed;
		$this->orderMode        = $orderMode;
		$this->pairingCode      = $pairingCode;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function setUrl(string $url): void
	{
		$this->url = $url;
	}

	public function getTransactionSpeed(): string
	{
		return $this->transactionSpeed;
	}

	public function setTransactionSpeed(string $transaction_speed): void
	{
		$this->transactionSpeed = $transaction_speed;
	}

	public function getOrderMode(): string
	{
		return $this->orderMode;
	}

	public function setOrderMode(string $order_mode): void
	{
		$this->orderMode = $order_mode;
	}

	public function getPairingCode(): ?string
	{
		return $this->pairingCode;
	}

	public function setPairingCode(?string $pairing_code): void
	{
		$this->pairingCode = $pairing_code;
	}
}
