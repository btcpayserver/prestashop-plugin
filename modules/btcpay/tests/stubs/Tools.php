<?php

/**
 * Minimal stand-in for PrestaShop's Tools class.
 */
class Tools
{
	public static function getShopDomainSsl(bool $keepHost = true): string
	{
		return 'https://shop.example';
	}
}
