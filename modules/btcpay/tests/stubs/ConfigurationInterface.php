<?php

namespace PrestaShop\PrestaShop\Core;

/**
 * Minimal stand-in for the PrestaShop configuration interface, so tests never
 * need a PrestaShop installation.
 */
interface ConfigurationInterface
{
	/**
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set($key, $value);
}
