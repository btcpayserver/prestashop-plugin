<?php

namespace PrestaShop\PrestaShop\Core\Form;

/**
 * Minimal stand-in for PrestaShop's form handler interface.
 */
interface FormHandlerInterface
{
	/**
	 * @return mixed
	 */
	public function getForm();

	public function save(array $data): array;
}
