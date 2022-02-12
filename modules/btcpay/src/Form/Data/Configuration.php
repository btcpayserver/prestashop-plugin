<?php

namespace BTCPay\Form\Data;

use Symfony\Component\Validator\Constraints as Assert;

class Configuration
{
	/**
	 * @Assert\Url()
	 * @Assert\NotBlank()
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * @Assert\NotBlank()
	 * @Assert\Choice(choices=\BTCPay\Constants::TRANSACTION_SPEEDS, message="Invalid transaction speed")
	 *
	 * @var string
	 */
	private $speed;

	/**
	 * @Assert\Choice(choices={true, false})
	 */
	private $shareMetadata;

	public function __construct(string $url, string $speed, bool $shareMetadata)
	{
		$this->url           = $url;
		$this->speed         = $speed;
		$this->shareMetadata = $shareMetadata;
	}

	public function getUrl(): ?string
	{
		return $this->url;
	}

	public function setUrl(?string $url): void
	{
		$this->url = $url;
	}

	public function getSpeed(): string
	{
		return $this->speed;
	}

	public function setSpeed(string $speed): void
	{
		$this->speed = $speed;
	}

	public function shareMetadata(): bool
	{
		return $this->shareMetadata;
	}

	public function setShareMetadata(bool $shareMetadata): void
	{
		$this->shareMetadata = $shareMetadata;
	}

	public function toArray(): array
	{
		return [
			'url'           => $this->url,
			'speed'         => $this->speed,
			'shareMetadata' => $this->shareMetadata,
		];
	}
}
