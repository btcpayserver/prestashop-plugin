<?php

namespace BTCPay\Server;

use BTCPayServer\Exception\RequestException;
use BTCPayServer\Result\StoreRateSettings;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Maps greenfield rate-configuration responses to a simple UI/notify status.
 */
class RateFallbackStatus
{
	public const CONFIGURED = 'configured';
	public const MISSING    = 'missing';
	public const UNKNOWN    = 'unknown';

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var string|null
	 */
	private $source;

	private function __construct(string $status, ?string $source = null)
	{
		$this->status = $status;
		$this->source = $source;
	}

	public static function configured(?string $source = null): self
	{
		return new self(self::CONFIGURED, $source);
	}

	public static function missing(): self
	{
		return new self(self::MISSING);
	}

	public static function unknown(): self
	{
		return new self(self::UNKNOWN);
	}

	public static function fromSettings(StoreRateSettings $settings): self
	{
		$data           = $settings->getData();
		$preferredSource = isset($data['preferredSource']) ? (string) $data['preferredSource'] : '';
		$isCustomScript  = !empty($data['isCustomScript']);

		if ('' !== $preferredSource || $isCustomScript) {
			return self::configured('' !== $preferredSource ? $preferredSource : 'custom script');
		}

		return self::missing();
	}

	public static function fromThrowable(\Throwable $throwable): self
	{
		if ($throwable instanceof RequestException && 404 === $throwable->getCode()) {
			return self::missing();
		}

		return self::unknown();
	}

	/**
	 * Resolve fallback status for a store using the greenfield StoreRate client.
	 */
	public static function resolve(StoreRate $storeRate, string $storeId): self
	{
		try {
			return self::fromSettings($storeRate->getSettingsBySource($storeId, StoreRate::SOURCE_FALLBACK));
		} catch (\Throwable $throwable) {
			return self::fromThrowable($throwable);
		}
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function getSource(): ?string
	{
		return $this->source;
	}

	public function isConfigured(): bool
	{
		return self::CONFIGURED === $this->status;
	}

	public function isMissing(): bool
	{
		return self::MISSING === $this->status;
	}
}
