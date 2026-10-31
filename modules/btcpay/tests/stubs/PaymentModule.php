<?php

/**
 * Minimal stand-in for PrestaShop's PaymentModule base class, so the module
 * class can be instantiated in tests.
 */
class PaymentModule
{
	public string $name = '';
	public string $tab = '';
	public string $version = '';
	public string $author = '';
	public array $ps_versions_compliancy = [];
	public array $controllers = [];
	public bool $bootstrap = false;
	public string $module_key = '';
	public bool $currencies = true;
	public string $currencies_mode = 'checkbox';
	public string $displayName = '';
	public string $description = '';
	public string $confirmUninstall = '';

	/**
	 * @var mixed
	 */
	public $warning = null;

	public function __construct()
	{
	}

	public function trans($id, array $parameters = [], $domain = null, $locale = null): string
	{
		return $id;
	}
}
