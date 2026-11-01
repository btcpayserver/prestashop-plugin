<?php

namespace BTCPay\Server;

use BTCPay\Constants;

if (!\defined('_PS_VERSION_')) {
	exit;
}

/**
 * Remembers that BTCPay Server could not be reached, so that the shop does not wait for it on every page.
 *
 * - a connection failure or timeout opens a short "unreachable" window (Constants::UNREACHABLE_WINDOW seconds),
 *   stored in the shop configuration so that it is shared by all PHP workers;
 * - while the window is open the module skips every remote call (payment option hidden, no warnings fetched);
 * - the stored host and API key are NEVER modified because of a network problem.
 */
class Health
{
	/**
	 * @var array<string, bool> per-request cache of Client::isValid(), keyed by host + API key hash
	 */
	private static $valid = [];

	/**
	 * @var array<string, mixed> per-request cache of server info, keyed by host + API key hash
	 */
	private static $serverInfo = [];

	public static function isUnreachable(): bool
	{
		return (int) \Configuration::getGlobalValue(Constants::CONFIGURATION_UNREACHABLE_UNTIL) > \time();
	}

	public static function markUnreachable(string $reason): void
	{
		// Only log once per window, to avoid filling the logs while BTCPay Server is down
		if (!self::isUnreachable()) {
			\PrestaShopLogger::addLog(
				\sprintf('[WARNING] BTCPay Server unreachable, remote calls paused for %d seconds (configuration kept): %s', Constants::UNREACHABLE_WINDOW, $reason),
				\PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
			);
		}

		\Configuration::updateGlobalValue(Constants::CONFIGURATION_UNREACHABLE_UNTIL, \time() + Constants::UNREACHABLE_WINDOW);
		self::$valid = [];
		self::$serverInfo = [];
	}

	public static function markReachable(): void
	{
		if ((int) \Configuration::getGlobalValue(Constants::CONFIGURATION_UNREACHABLE_UNTIL) > 0) {
			\Configuration::updateGlobalValue(Constants::CONFIGURATION_UNREACHABLE_UNTIL, 0);
		}
	}

	public static function getValid(string $key): ?bool
	{
		return self::$valid[$key] ?? null;
	}

	public static function setValid(string $key, bool $valid): void
	{
		self::$valid[$key] = $valid;
	}

	/**
	 * @return mixed|null
	 */
	public static function getServerInfo(string $key)
	{
		return self::$serverInfo[$key] ?? null;
	}

	/**
	 * @param mixed $info
	 */
	public static function setServerInfo(string $key, $info): void
	{
		self::$serverInfo[$key] = $info;
	}

	public static function reset(): void
	{
		self::$valid = [];
		self::$serverInfo = [];
	}
}
