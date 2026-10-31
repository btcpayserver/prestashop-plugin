<?php

namespace Symfony\Component\Validator\Validator;

/**
 * Minimal stand-in for Symfony's validator interface.
 */
interface ValidatorInterface
{
	/**
	 * @param mixed $value
	 * @param mixed $constraints
	 * @param mixed $groups
	 *
	 * @return mixed
	 */
	public function validate($value, $constraints = null, $groups = null);
}
