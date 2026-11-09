<?php

/**
 * Test double for PrestaShop's PrestaShopLogger: collects log entries in memory.
 */
class PrestaShopLogger
{
	public const LOG_SEVERITY_LEVEL_INFORMATIVE = 1;
	public const LOG_SEVERITY_LEVEL_WARNING     = 3;
	public const LOG_SEVERITY_LEVEL_ERROR       = 4;

	/** @var array<int, array{severity: int, message: string}> */
	public static array $entries = [];

	public static function addLog(string $message, int $severity = self::LOG_SEVERITY_LEVEL_INFORMATIVE, ?int $errorCode = null, ?string $objectType = null, ?int $objectId = null): void
	{
		self::$entries[] = ['severity' => $severity, 'message' => $message];
	}

	public static function reset(): void
	{
		self::$entries = [];
	}
}
