<?php

declare(strict_types=1);

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
