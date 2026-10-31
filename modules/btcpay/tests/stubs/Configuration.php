<?php

namespace PrestaShop\PrestaShop\Adapter;

use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;

/**
 * In-memory configuration for tests that must not access a PrestaShop database.
 */
class Configuration implements ShopConfigurationInterface
{
	public static array $values = [];

	public function get($key, $default = null)
	{
		return self::$values[$key] ?? $default;
	}

	public function set($key, $value): void
	{
		self::$values[$key] = $value;
	}
}
