<?php

/**
 * Minimal stand-in for PrestaShop's controller context.
 */
class FakeShopContext
{
	public FakeShop $shop;

	public function __construct()
	{
		$this->shop = new FakeShop();
	}
}
