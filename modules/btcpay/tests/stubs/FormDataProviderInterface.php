<?php

namespace PrestaShop\PrestaShop\Core\Form;

/**
 * Minimal stand-in for PrestaShop's form data provider interface.
 */
interface FormDataProviderInterface
{
	/**
	 * @return mixed
	 */
	public function getData();

	public function setData(array $data): array;
}
