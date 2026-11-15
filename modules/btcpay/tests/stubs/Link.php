<?php

declare(strict_types=1);

/**
 * Generates module URLs without booting PrestaShop.
 */
class Link
{
	public function getModuleLink(string $module, string $controller, array $parameters = [], bool $ssl = false): string
	{
		return ($ssl ? 'https' : 'http') . '://shop.example/module/' . $module . '/' . $controller;
	}
}
