<?php

// phpcs:disable Squiz.Functions.GlobalFunction -- PrestaShop core globals required by the code under test
if (!\function_exists('pSQL')) {
	/**
	 * Minimal stand-in for PrestaShop's pSQL(): the fake database keeps raw strings.
	 *
	 * @param mixed $string
	 * @param bool  $html
	 *
	 * @return mixed
	 */
	function pSQL($string, $html = false)
	{
		return $string;
	}
}
// phpcs:enable Squiz.Functions.GlobalFunction
